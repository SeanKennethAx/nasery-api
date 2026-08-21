<?php

namespace App\Http\Controllers;

use App\Models\ClientProfile;
use App\Models\Inquiry;
use App\Models\Organizer;
use App\Models\Quotation;
use App\Notifications\QuotationReceivedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function store(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'inquiry_id' => [
                'required',
                'exists:inquiries,id',
            ],

            'quotation_amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'package_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'timeline' => [
                'nullable',
                'string',
                'max:255',
            ],

            'quotation_details' => [
                'nullable',
                'string',
            ],

            'inclusions' => [
                'nullable',
                'array',
            ],

            'inclusions.*' => [
                'string',
                'max:255',
            ],
        ]);

        $user = $request->user();

        if (
            !$user ||
            $user->role !== 'organizer'
        ) {
            return response()->json([
                'message' =>
                'Only organizers can submit quotations.',
            ], 403);
        }

        $organizer = Organizer::where(
            'user_id',
            $user->id
        )->first();

        if (!$organizer) {
            return response()->json([
                'message' =>
                'Organizer profile not found.',
            ], 404);
        }

        $inquiry = Inquiry::find(
            $validated['inquiry_id']
        );

        if (!$inquiry) {
            return response()->json([
                'message' =>
                'Inquiry not found.',
            ], 404);
        }

        if (
            in_array(
                $inquiry->status,
                [
                    'awarded',
                    'cancelled',
                ],
                true
            )
        ) {
            return response()->json([
                'message' =>
                'This inquiry is no longer accepting quotations.',
            ], 422);
        }

        $existingQuotation =
            Quotation::where(
                'inquiry_id',
                $validated['inquiry_id']
            )
            ->where(
                'organizer_id',
                $organizer->id
            )
            ->first();

        if ($existingQuotation) {
            return response()->json([
                'message' =>
                'You already submitted a quotation for this inquiry.',
            ], 422);
        }

        $quotation =
            DB::transaction(
                function () use (
                    $validated,
                    $organizer,
                    $inquiry
                ) {
                    $quotation =
                        Quotation::create([
                            'inquiry_id' =>
                            $validated['inquiry_id'],

                            'organizer_id' =>
                            $organizer->id,

                            'quotation_amount' =>
                            $validated['quotation_amount'],

                            'package_name' =>
                            $validated['package_name']
                                ?? null,

                            'timeline' =>
                            $validated['timeline']
                                ?? null,

                            'quotation_details' =>
                            $validated['quotation_details']
                                ?? null,

                            'quotation_status' =>
                            'pending',
                        ]);

                    foreach (
                        $validated['inclusions'] ?? []
                        as $description
                    ) {
                        $description =
                            trim($description);

                        if (!$description) {
                            continue;
                        }

                        $quotation
                            ->inclusions()
                            ->create([
                                'description' =>
                                $description,
                            ]);
                    }

                    if (
                        $inquiry->status ===
                        'open'
                    ) {
                        $inquiry->update([
                            'status' =>
                            'receiving_quotations',
                        ]);
                    }

                    return $quotation;
                }
            );

        $inquiry->loadMissing(
            'client.user'
        );

        $clientUser =
            $inquiry->client?->user;

        if ($clientUser) {
            $clientUser->notify(
                new QuotationReceivedNotification(
                    $quotation
                )
            );
        }


        $quotation->load([
            'inclusions',
            'organizer.user',
        ]);

        return response()->json([
            'message' =>
            'Quotation submitted successfully.',

            'data' =>
            $quotation,
        ], 201);
    }

    public function inquiryQuotations(
        Request $request,
        Inquiry $inquiry
    ): JsonResponse {
        $user = $request->user();

        if (
            !$user ||
            $user->role !== 'client'
        ) {
            return response()->json([
                'message' =>
                'Only clients can view quotations.',
            ], 403);
        }

        $client =
            ClientProfile::where(
                'user_id',
                $user->id
            )->first();

        if (!$client) {
            return response()->json([
                'message' =>
                'Client profile not found.',
            ], 404);
        }

        if (
            (int) $inquiry->client_id !==
            (int) $client->id
        ) {
            return response()->json([
                'message' =>
                'You are not allowed to view quotations for this inquiry.',
            ], 403);
        }

        $quotations =
            $inquiry
            ->quotations()
            ->with([
                'organizer.user',
                'inclusions',
            ])
            ->orderBy(
                'created_at',
                'desc'
            )
            ->get();

        return response()->json([
            'data' =>
            $quotations,
        ]);
    }

    public function accept(
        Request $request,
        Quotation $quotation
    ): JsonResponse {
        $user = $request->user();

        if (
            !$user ||
            $user->role !== 'client'
        ) {
            return response()->json([
                'message' =>
                'Only clients can accept quotations.',
            ], 403);
        }

        $client =
            ClientProfile::where(
                'user_id',
                $user->id
            )->first();

        if (!$client) {
            return response()->json([
                'message' =>
                'Client profile not found.',
            ], 404);
        }

        $inquiry =
            $quotation->inquiry;

        if (!$inquiry) {
            return response()->json([
                'message' =>
                'Inquiry not found.',
            ], 404);
        }

        if (
            (int) $inquiry->client_id !==
            (int) $client->id
        ) {
            return response()->json([
                'message' =>
                'You are not allowed to accept this quotation.',
            ], 403);
        }

        if (
            $inquiry->status ===
            'awarded'
        ) {
            return response()->json([
                'message' =>
                'This inquiry has already been awarded.',
            ], 422);
        }

        if (
            $quotation->quotation_status !==
            'pending'
        ) {
            return response()->json([
                'message' =>
                'This quotation is no longer available.',
            ], 422);
        }

        DB::transaction(
            function () use (
                $quotation,
                $inquiry
            ) {
                Quotation::where(
                    'inquiry_id',
                    $inquiry->id
                )
                    ->where(
                        'id',
                        '!=',
                        $quotation->id
                    )
                    ->where(
                        'quotation_status',
                        'pending'
                    )
                    ->update([
                        'quotation_status' =>
                        'rejected',
                    ]);

                $quotation->update([
                    'quotation_status' =>
                    'accepted',
                ]);

                $inquiry->update([
                    'status' =>
                    'awarded',

                    'awarded_quotation_id' =>
                    $quotation->id,
                ]);
            }
        );

        return response()->json([
            'message' =>
            'Quotation accepted successfully.',

            'data' => [
                'quotation_id' =>
                $quotation->id,

                'inquiry_id' =>
                $inquiry->id,

                'status' =>
                'awarded',
            ],
        ]);
    }
    public function organizerQuotations(
        Request $request
    ) {
        $user = $request->user();

        if (
            !$user ||
            $user->role !== 'organizer'
        ) {
            return response()->json([
                'message' =>
                'Only organizers can view their quotations.',
            ], 403);
        }

        $organizer =
            Organizer::where(
                'user_id',
                $user->id
            )->first();

        if (!$organizer) {
            return response()->json([
                'message' =>
                'Organizer profile not found.',
            ], 404);
        }

        $quotations =
            Quotation::query()
            ->where(
                'organizer_id',
                $organizer->id
            )
            ->with([
                'inquiry',
                'inclusions',
            ])
            ->latest()
            ->get();

        return response()->json([
            'data' => $quotations,
        ]);
    }
}

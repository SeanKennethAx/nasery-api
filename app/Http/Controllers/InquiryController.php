<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'event_type' => [
                'required',
                'string',
                'max:100',
            ],

            'event_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],

            'location' => [
                'nullable',
                'string',
                'max:500',
                'required_without:venue_address',
            ],

            'venue_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'venue_address' => [
                'nullable',
                'string',
                'max:1000',
                'required_without:location',
            ],

            'google_place_id' => [
                'nullable',
                'string',
                'max:255',
            ],

            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
                'required_with:google_place_id,longitude',
            ],

            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
                'required_with:google_place_id,latitude',
            ],

            'expected_guests' => [
                'required',
                'integer',
                'min:1',
            ],

            'budget_range' => [
                'required',
                'string',
                'max:100',
            ],

            'additional_details' => [
                'nullable',
                'string',
            ],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'client') {
            return response()->json([
                'message' => 'Only clients can create inquiries.',
            ], 403);
        }

        $client = $user->client;

        if (!$client) {
            return response()->json([
                'message' => 'Client profile not found.',
            ], 404);
        }

        $venueAddress =
            $validated['venue_address']
            ?? $validated['location']
            ?? null;

        $location =
            $validated['location']
            ?? $venueAddress;

        $inquiry = Inquiry::create([
            'client_id' => $client->id,

            'event_title' =>
            $validated['event_title']
                ?? null,

            'event_type' =>
            $validated['event_type'],

            'event_date' =>
            $validated['event_date'],

            'start_time' =>
            $validated['start_time'],

            'end_time' =>
            $validated['end_time'],

            'location' =>
            $location,

            'venue_name' =>
            $validated['venue_name']
                ?? null,

            'venue_address' =>
            $venueAddress,

            'google_place_id' =>
            $validated['google_place_id']
                ?? null,

            'latitude' =>
            $validated['latitude']
                ?? null,

            'longitude' =>
            $validated['longitude']
                ?? null,

            'expected_guests' =>
            $validated['expected_guests'],

            'budget_range' =>
            $validated['budget_range'],

            'additional_details' =>
            $validated['additional_details']
                ?? null,

            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Inquiry submitted successfully.',
            'data' => $inquiry,
        ], 201);
    }

    public function clientInquiries(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'client') {
            return response()->json([
                'message' => 'Only clients can view client inquiries.',
            ], 403);
        }

        $client = $user->client;

        if (!$client) {
            return response()->json([
                'message' => 'Client profile not found.',
            ], 404);
        }

        $inquiries = Inquiry::query()
            ->where(
                'client_id',
                $client->id
            )
            ->with([
                'awardedQuotation',
                'event',
            ])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Inquiries retrieved successfully.',
            'data' => $inquiries,
        ]);
    }

    public function matching(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'organizer') {
            return response()->json([
                'message' => 'Only organizers can view matching inquiries.',
            ], 403);
        }

        $organizer = $user->organizer;

        if (!$organizer) {
            return response()->json([
                'message' => 'Organizer profile not found.',
            ], 404);
        }

        $tags = $organizer->tags;

        if (is_string($tags)) {
            $tags = json_decode(
                $tags,
                true
            ) ?? [];
        }

        $tags = $tags ?? [];

        if (empty($tags)) {
            return response()->json([
                'message' => 'Matching inquiries retrieved successfully.',
                'data' => [],
            ]);
        }

        $inquiries = Inquiry::query()
            ->whereIn(
                'event_type',
                $tags
            )
            ->whereIn(
                'status',
                [
                    'open',
                    'receiving_quotations',
                ]
            )
            ->whereDoesntHave(
                'quotations',
                function ($query) use ($organizer) {
                    $query->where(
                        'organizer_id',
                        $organizer->id
                    );
                }
            )
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Matching inquiries retrieved successfully.',
            'data' => $inquiries,
        ]);
    }
}

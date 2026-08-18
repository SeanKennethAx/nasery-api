<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    /**
     * Client creates a new inquiry.
     */
    public function store(
        Request $request
    ): JsonResponse {
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
            ],

            'location' => [
                'required',
                'string',
                'max:255',
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
                'message' =>
                'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'client') {
            return response()->json([
                'message' =>
                'Only clients can create inquiries.',
            ], 403);
        }

        $client = $user->client;

        if (!$client) {
            return response()->json([
                'message' =>
                'Client profile not found.',
            ], 404);
        }

        $inquiry = Inquiry::create([
            'client_id' =>
            $client->id,

            'event_title' =>
            $validated['event_title'] ?? null,

            'event_type' =>
            $validated['event_type'],

            'event_date' =>
            $validated['event_date'],

            'location' =>
            $validated['location'],

            'expected_guests' =>
            $validated['expected_guests'],

            'budget_range' =>
            $validated['budget_range'],

            'additional_details' =>
            $validated['additional_details'] ?? null,
        ]);

        return response()->json([
            'message' =>
            'Inquiry submitted successfully.',

            'data' =>
            $inquiry,
        ], 201);
    }


    /**
     * Get all inquiries created
     * by the authenticated client.
     */
    public function clientInquiries(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' =>
                'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'client') {
            return response()->json([
                'message' =>
                'Only clients can view their inquiries.',
            ], 403);
        }

        $client = $user->client;

        if (!$client) {
            return response()->json([
                'message' =>
                'Client profile not found.',
            ], 404);
        }

        $inquiries = Inquiry::query()
            ->where(
                'client_id',
                $client->id
            )
            ->latest()
            ->get();

        return response()->json([
            'data' =>
            $inquiries,
        ]);
    }


    /**
     * Organizer matching inquiries.
     *
     * Only inquiries whose event_type
     * matches one of the organizer's tags
     * will be returned.
     */
    public function matching(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' =>
                'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'organizer') {
            return response()->json([
                'message' =>
                'Only organizers can view matching inquiries.',
            ], 403);
        }

        $organizer = $user->organizer;

        if (!$organizer) {
            return response()->json([
                'message' =>
                'Organizer profile not found.',
            ], 404);
        }

        $tags = $organizer->tags ?? [];

        if (empty($tags)) {
            return response()->json([
                'data' => [],
            ]);
        }

        $inquiries = Inquiry::query()
            ->with([
                'client.user',
            ])
            ->whereIn(
                'event_type',
                $tags
            )
            ->latest()
            ->get();

        return response()->json([
            'data' =>
            $inquiries,
        ]);
    }
}

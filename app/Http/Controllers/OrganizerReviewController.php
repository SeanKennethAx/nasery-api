<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\OrganizerReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizerReviewController extends Controller
{
    public function index(
        Request $request,
        Organizer $organizer
    ): JsonResponse {
        $reviews = OrganizerReview::query()
            ->where('organizer_id', $organizer->id)
            ->where('is_visible', true)
            ->with([
                'client.user',
                'event',
                'inquiry',
            ])
            ->latest()
            ->get();

        $averageRating = OrganizerReview::query()
            ->where('organizer_id', $organizer->id)
            ->where('is_visible', true)
            ->avg('rating');

        $reviewsCount = OrganizerReview::query()
            ->where('organizer_id', $organizer->id)
            ->where('is_visible', true)
            ->count();

        return response()->json([
            'message' => 'Organizer reviews retrieved successfully.',
            'data' => [
                'organizer' => [
                    'id' => $organizer->id,
                    'company_name' => $organizer->company_name,
                ],

                'average_rating' => round(
                    (float) ($averageRating ?? 0),
                    1
                ),

                'reviews_count' => $reviewsCount,

                'reviews' => $reviews,
            ],
        ]);
    }

    public function store(
        Request $request,
        Organizer $organizer
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'client') {
            return response()->json([
                'message' => 'Only clients can submit organizer reviews.',
            ], 403);
        }

        $client = $user->client;

        if (!$client) {
            return response()->json([
                'message' => 'Client profile not found.',
            ], 404);
        }

        $validated = $request->validate([
            'event_id' => [
                'required',
                'integer',
                'exists:events,id',
            ],

            'inquiry_id' => [
                'nullable',
                'integer',
                'exists:inquiries,id',
            ],

            'rating' => [
                'required',
                'integer',
                'between:1,5',
            ],

            'review' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $event = Event::query()
            ->whereKey($validated['event_id'])
            ->firstOrFail();

        if ((int) $event->client_id !== (int) $client->id) {
            return response()->json([
                'message' => 'You cannot review an event that does not belong to you.',
            ], 403);
        }

        if ((int) $event->organizer_id !== (int) $organizer->id) {
            return response()->json([
                'message' => 'This organizer was not assigned to the selected event.',
            ], 422);
        }

        $existingReview = OrganizerReview::query()
            ->where('organizer_id', $organizer->id)
            ->where('client_id', $client->id)
            ->where('event_id', $event->id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'You have already reviewed this organizer for this event.',
            ], 422);
        }

        $review = OrganizerReview::create([
            'organizer_id' => $organizer->id,
            'client_id' => $client->id,
            'event_id' => $event->id,

            'inquiry_id' =>
            $validated['inquiry_id']
                ?? $event->inquiry_id
                ?? null,

            'rating' => $validated['rating'],

            'review' =>
            $validated['review']
                ?? null,

            'is_visible' => true,
        ]);

        $review->load([
            'client.user',
            'event',
            'inquiry',
        ]);

        return response()->json([
            'message' => 'Review submitted successfully.',
            'data' => $review,
        ], 201);
    }

    public function organizerReviews(
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
                'message' => 'Only organizers can access these reviews.',
            ], 403);
        }

        $organizer = $user->organizer;

        if (!$organizer) {
            return response()->json([
                'message' => 'Organizer profile not found.',
            ], 404);
        }

        $reviews = OrganizerReview::query()
            ->where('organizer_id', $organizer->id)
            ->where('is_visible', true)
            ->with([
                'client.user',
                'event',
                'inquiry',
            ])
            ->latest()
            ->get();

        $averageRating = OrganizerReview::query()
            ->where('organizer_id', $organizer->id)
            ->where('is_visible', true)
            ->avg('rating');

        return response()->json([
            'message' => 'Reviews retrieved successfully.',
            'data' => [
                'average_rating' => round(
                    (float) ($averageRating ?? 0),
                    1
                ),

                'reviews_count' => $reviews->count(),

                'reviews' => $reviews,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ClientProfile;
use App\Models\Event;
use App\Models\EventRegistrationSetting;
use App\Models\EventTicket;
use App\Models\Inquiry;
use App\Models\Organizer;
use App\Models\Quotation;
use App\Notifications\QuotationReceivedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        if (!$user) {
            return response()->json([
                'message' =>
                'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'organizer') {
            return response()->json([
                'message' =>
                'Only organizers can submit quotations.',
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

        $inquiry =
            Inquiry::find(
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
            'inquiry',
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

        if (!$user) {
            return response()->json([
                'message' =>
                'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'client') {
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
                'event',
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

        if (!$user) {
            return response()->json([
                'message' =>
                'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'client') {
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

        $quotation->load([
            'organizer.user',
            'inclusions',
        ]);

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

                'planning_started' =>
                false,

                'event' =>
                null,

                'quotation' =>
                $quotation,
            ],
        ]);
    }

    public function startPlanning(
        Request $request,
        Quotation $quotation
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' =>
                'Unauthenticated.',
            ], 401);
        }

        if (
            $user->role !==
            'organizer'
        ) {
            return response()->json([
                'message' =>
                'Only organizers can start event planning.',
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

        if (
            (int) $quotation->organizer_id !==
            (int) $organizer->id
        ) {
            return response()->json([
                'message' =>
                'You are not allowed to manage this quotation.',
            ], 403);
        }

        if (
            $quotation->quotation_status !==
            'accepted'
        ) {
            return response()->json([
                'message' =>
                'Only an accepted quotation can start event planning.',
            ], 422);
        }

        $quotation->loadMissing(
            'inquiry'
        );

        $inquiry =
            $quotation->inquiry;

        if (!$inquiry) {
            return response()->json([
                'message' =>
                'Inquiry not found.',
            ], 404);
        }

        if (
            $inquiry->status !==
            'awarded'
        ) {
            return response()->json([
                'message' =>
                'This inquiry has not been awarded.',
            ], 422);
        }

        if (
            (int) $inquiry->awarded_quotation_id !==
            (int) $quotation->id
        ) {
            return response()->json([
                'message' =>
                'This quotation is not the awarded quotation.',
            ], 422);
        }

        $existingEvent =
            Event::where(
                'quotation_id',
                $quotation->id
            )->first();

        if ($existingEvent) {
            $existingEvent->load([
                'inquiry',
                'quotation.inclusions',
                'organizer',
                'client',
                'ticketTypes',
                'eventLocation',
                'registrationSettings',
            ]);

            return response()->json([
                'message' =>
                'Event planning has already started.',

                'data' => [
                    'planning_started' =>
                    true,

                    'event' =>
                    $existingEvent,
                ],
            ]);
        }

        $event =
            DB::transaction(
                function () use (
                    $quotation,
                    $inquiry
                ) {
                    $event = Event::create([
                        'inquiry_id' =>
                        $inquiry->id,

                        'quotation_id' =>
                        $quotation->id,

                        'organizer_id' =>
                        $quotation->organizer_id,

                        'client_id' =>
                        $inquiry->client_id,

                        'name' =>
                        $inquiry->event_title
                            ?: (
                                $inquiry->event_type .
                                ' Event'
                            ),

                        'event_type' =>
                        $inquiry->event_type,

                        'description' =>
                        $quotation->quotation_details
                            ?? $inquiry->additional_details,

                        'event_date' =>
                        $inquiry->event_date,

                        'expected_guests' =>
                        $inquiry->expected_guests,

                        'start_time' =>
                        $inquiry->start_time,

                        'end_time' =>
                        $inquiry->end_time,

                        'status' =>
                        'draft',

                    ]);

                    $event->eventLocation()->create([
                        'venue_name' => $inquiry->location,
                        'venue_address' => $inquiry->location,
                    ]);

                    $event->registrationSettings()->create([
                        'public_registration' => false,
                    ]);

                    return $event;
                }
            );

        $event->load([
            'inquiry',
            'quotation.inclusions',
            'organizer',
            'client',
            'ticketTypes',
            'eventLocation',
            'registrationSettings',
        ]);

        return response()->json([
            'message' =>
            'Event planning started successfully.',

            'data' => [
                'planning_started' =>
                true,

                'event' =>
                $event,
            ],
        ], 201);
    }

    public function organizerQuotations(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' =>
                'Unauthenticated.',
            ], 401);
        }

        if (
            $user->role !==
            'organizer'
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
                'event',
            ])
            ->latest()
            ->get();

        return response()->json([
            'data' =>
            $quotations,
        ]);
    }

    public function eventData(
        Request $request,
        Quotation $quotation
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' =>
                'Unauthenticated.',
            ], 401);
        }

        if (
            $user->role !==
            'organizer'
        ) {
            return response()->json([
                'message' =>
                'Only organizers can view quotation event data.',
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

        if (
            (int) $quotation->organizer_id !==
            (int) $organizer->id
        ) {
            return response()->json([
                'message' =>
                'You are not allowed to view this quotation.',
            ], 403);
        }

        $quotation->load([
            'inquiry.client.user',
            'inclusions',
            'event.ticketTypes',
            'event.eventLocation',
            'event.registrationSettings',
        ]);

        $inquiry =
            $quotation->inquiry;

        if (!$inquiry) {
            return response()->json([
                'message' =>
                'Inquiry not found.',
            ], 404);
        }

        return response()->json([
            'message' =>
            'Quotation event data retrieved successfully.',

            'data' => [
                'planning_started' =>
                $quotation->event !== null,

                'quotation' => [
                    'id' =>
                    $quotation->id,

                    'quotation_amount' =>
                    $quotation->quotation_amount,

                    'package_name' =>
                    $quotation->package_name,

                    'timeline' =>
                    $quotation->timeline,

                    'quotation_details' =>
                    $quotation->quotation_details,

                    'quotation_status' =>
                    $quotation->quotation_status,

                    'inclusions' =>
                    $quotation->inclusions,
                ],

                'inquiry' => [
                    'id' =>
                    $inquiry->id,

                    'event_title' =>
                    $inquiry->event_title,

                    'event_type' =>
                    $inquiry->event_type,

                    'event_date' =>
                    $inquiry->event_date,

                    'location' =>
                    $inquiry->location,

                    'expected_guests' =>
                    $inquiry->expected_guests,

                    'budget_range' =>
                    $inquiry->budget_range,

                    'additional_details' =>
                    $inquiry->additional_details,

                    'status' =>
                    $inquiry->status,

                    'awarded_quotation_id' =>
                    $inquiry->awarded_quotation_id,
                ],

                'client' =>
                $inquiry->client,

                'event' =>
                $quotation->event,
            ],
        ]);
    }
    public function clientEventDetails(
        Request $request,
        Inquiry $inquiry
    ): JsonResponse {
        $user =
            $request->user();

        if (!$user) {
            return response()->json([
                'message' =>
                'Unauthenticated.',
            ], 401);
        }

        if (
            $user->role !==
            'client'
        ) {
            return response()->json([
                'message' =>
                'Only clients can view event details.',
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
                'You are not allowed to view this event.',
            ], 403);
        }

        if (
            $inquiry->status !==
            'awarded'
        ) {
            return response()->json([
                'message' =>
                'This inquiry has not been awarded yet.',
            ], 422);
        }

        if (
            !$inquiry->awarded_quotation_id
        ) {
            return response()->json([
                'message' =>
                'No awarded quotation was found for this inquiry.',
            ], 404);
        }

        $quotation =
            Quotation::with([
                'organizer.user',
                'inclusions',
                'event.ticketTypes',
            ])
            ->find(
                $inquiry->awarded_quotation_id
            );

        if (!$quotation) {
            return response()->json([
                'message' =>
                'Awarded quotation not found.',
            ], 404);
        }

        if (!$quotation->event) {
            return response()->json([
                'message' =>
                'The organizer has not started event planning yet.',

                'status' =>
                'planning_not_started',

                'data' => [
                    'planning_started' =>
                    false,

                    'inquiry' => [
                        'id' =>
                        $inquiry->id,

                        'event_title' =>
                        $inquiry->event_title,

                        'event_type' =>
                        $inquiry->event_type,

                        'event_date' =>
                        $inquiry->event_date,

                        'location' =>
                        $inquiry->location,

                        'expected_guests' =>
                        $inquiry->expected_guests,

                        'budget_range' =>
                        $inquiry->budget_range,

                        'additional_details' =>
                        $inquiry->additional_details,

                        'status' =>
                        $inquiry->status,
                    ],

                    'quotation' => [
                        'id' =>
                        $quotation->id,

                        'quotation_amount' =>
                        $quotation->quotation_amount,

                        'package_name' =>
                        $quotation->package_name,

                        'timeline' =>
                        $quotation->timeline,

                        'quotation_details' =>
                        $quotation->quotation_details,

                        'quotation_status' =>
                        $quotation->quotation_status,

                        'inclusions' =>
                        $quotation->inclusions,
                    ],

                    'organizer' =>
                    $quotation->organizer,

                    'event' =>
                    null,
                ],
            ]);
        }

        $event =
            $quotation->event;
        if (
            $event->public_registration &&
            !$event->public_registration_token
        ) {
            do {
                $registrationToken =
                    Str::random(64);
            } while (
                EventRegistrationSetting::query()
                ->where(
                    'public_registration_token',
                    $registrationToken
                )
                ->exists()
            );

            $event->registrationSettings()->updateOrCreate(
                ['event_id' => $event->id],
                ['public_registration_token' => $registrationToken]
            );

            $event->refresh();

            $event->load(['ticketTypes', 'eventLocation', 'registrationSettings']);
        }
        $ticketTypes =
            $event
            ->ticketTypes
            ->map(
                function ($ticketType) use ($event) {
                    $issued =
                        EventTicket::query()
                        ->where(
                            'event_id',
                            $event->id
                        )
                        ->where(
                            'event_ticket_type_id',
                            $ticketType->id
                        )
                        ->where(
                            'status',
                            '!=',
                            'cancelled'
                        )
                        ->count();

                    $remaining =
                        $ticketType->capacity > 0
                        ? max(
                            $ticketType->capacity -
                                $issued,
                            0
                        )
                        : null;

                    $soldOut =
                        $ticketType->capacity > 0 &&
                        $issued >=
                        $ticketType->capacity;

                    return [
                        'id' =>
                        $ticketType->id,

                        'event_id' =>
                        $ticketType->event_id,

                        'name' =>
                        $ticketType->name,

                        'price' =>
                        $ticketType->price,

                        'capacity' =>
                        $ticketType->capacity,

                        'issued' =>
                        $issued,

                        'remaining' =>
                        $remaining,

                        'sold_count' =>
                        $issued,

                        'remaining_capacity' =>
                        $remaining,

                        'sold_out' =>
                        $soldOut,
                    ];
                }
            )
            ->values();

        return response()->json([
            'message' =>
            'Event details retrieved successfully.',

            'status' =>
            'planning_started',

            'data' => [
                'planning_started' =>
                true,

                'inquiry' => [
                    'id' =>
                    $inquiry->id,

                    'event_title' =>
                    $inquiry->event_title,

                    'event_type' =>
                    $inquiry->event_type,

                    'event_date' =>
                    $inquiry->event_date,

                    'location' =>
                    $inquiry->location,

                    'expected_guests' =>
                    $inquiry->expected_guests,

                    'budget_range' =>
                    $inquiry->budget_range,

                    'additional_details' =>
                    $inquiry->additional_details,

                    'status' =>
                    $inquiry->status,
                ],

                'quotation' => [
                    'id' =>
                    $quotation->id,

                    'quotation_amount' =>
                    $quotation->quotation_amount,

                    'package_name' =>
                    $quotation->package_name,

                    'timeline' =>
                    $quotation->timeline,

                    'quotation_details' =>
                    $quotation->quotation_details,

                    'quotation_status' =>
                    $quotation->quotation_status,

                    'inclusions' =>
                    $quotation->inclusions,
                ],

                'organizer' =>
                $quotation->organizer,

                'event' => [
                    'id' =>
                    $event->id,

                    'name' =>
                    $event->name,

                    'event_type' =>
                    $event->event_type,

                    'description' =>
                    $event->description,

                    'event_date' =>
                    $event->event_date,

                    'location' =>
                    $event->location,

                    'expected_guests' =>
                    $event->expected_guests,

                    'start_time' =>
                    $event->start_time,

                    'end_time' =>
                    $event->end_time,

                    'status' =>
                    $event->status,

                    'public_registration' =>
                    (bool)
                    $event->public_registration,

                    'public_registration_token' =>
                    $event->public_registration_token,

                    'require_approval' =>
                    (bool)
                    $event->require_approval,

                    'waitlist_enabled' =>
                    (bool)
                    $event->waitlist_enabled,

                    'contact_name' =>
                    $event->contact_name,

                    'contact_email' =>
                    $event->contact_email,

                    'contact_phone' =>
                    $event->contact_phone,

                    'ticket_types' =>
                    $ticketTypes,
                ],
            ],
        ]);
    }
}

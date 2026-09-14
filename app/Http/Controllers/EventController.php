<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::query()
            ->managedBy($request->user())
            ->with([
                'ticketTypes',
                'eventLocation',
                'registrationSettings',
            ])
            ->latest()
            ->get();

        return response()->json([
            'data' => $events,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => [
                'nullable',
                'integer',
            ],

            'inquiry_id' => [
                'nullable',
                'integer',
            ],

            'quotation_id' => [
                'nullable',
                'integer',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'event_type' => [
                'required',
                'string',
                'max:100',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'event_date' => [
                'nullable',
                'date',
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],

            'expected_guests' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'start_time' => [
                'nullable',
            ],

            'end_time' => [
                'nullable',
            ],

            'status' => [
                'required',
                'in:draft,confirmed,ongoing,completed,cancelled,published',
            ],

            'public_registration' => [
                'nullable',
                'boolean',
            ],

            'require_approval' => [
                'nullable',
                'boolean',
            ],

            'waitlist_enabled' => [
                'nullable',
                'boolean',
            ],

            'contact_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'contact_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'contact_phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'ticket_types' => [
                'nullable',
                'array',
            ],

            'ticket_types.*.name' => [
                'required',
                'string',
                'max:255',
            ],

            'ticket_types.*.price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'ticket_types.*.capacity' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        $event = DB::transaction(
            function () use (
                $request,
                $validated
            ) {
                $ticketTypes =
                    $validated['ticket_types']
                    ?? [];

                unset(
                    $validated['ticket_types']
                );

                $location = $validated['location'] ?? null;
                $settings = collect($validated)->only([
                    'public_registration',
                    'require_approval',
                    'waitlist_enabled',
                    'contact_name',
                    'contact_email',
                    'contact_phone',
                ])->all();

                unset(
                    $validated['location'],
                    $validated['public_registration'],
                    $validated['require_approval'],
                    $validated['waitlist_enabled'],
                    $validated['contact_name'],
                    $validated['contact_email'],
                    $validated['contact_phone'],
                );

                $organizer = $request->user()->organizer;

                abort_unless($organizer, 404, 'Organizer profile not found.');

                $event = Event::create([
                    ...$validated,

                    'organizer_id' => $organizer->id,
                ]);

                $event->eventLocation()->create([
                    'venue_name' => $location,
                    'venue_address' => $location,
                ]);

                if (! empty($settings['public_registration'])) {
                    $settings['public_registration_token'] = Str::random(48);
                }

                $event->registrationSettings()->create($settings);

                foreach (
                    $ticketTypes as $ticketType
                ) {
                    $event
                        ->ticketTypes()
                        ->create([
                            'name' => $ticketType['name'],

                            'price' => $ticketType['price'],

                            'capacity' => $ticketType['capacity'],
                        ]);
                }

                return $event;
            }
        );

        $event->load([
            'ticketTypes',
            'eventLocation',
            'registrationSettings',
        ]);

        return response()->json([
            'message' => 'Event created successfully.',

            'data' => $event,

            'event' => $event,
        ], 201);
    }

    public function show(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->isManagedBy($request->user()),
            403
        );

        $event->load([
            'ticketTypes',
            'eventLocation',
            'registrationSettings',
        ]);

        return response()->json([
            'data' => $event,
        ]);
    }

    public function update(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->isManagedBy($request->user()),
            403
        );

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'event_type' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'event_date' => [
                'nullable',
                'date',
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],

            'expected_guests' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'start_time' => [
                'nullable',
            ],

            'end_time' => [
                'nullable',
            ],

            'status' => [
                'sometimes',
                'required',
                'in:draft,confirmed,ongoing,completed,cancelled,published',
            ],

            'public_registration' => [
                'nullable',
                'boolean',
            ],

            'require_approval' => [
                'nullable',
                'boolean',
            ],

            'waitlist_enabled' => [
                'nullable',
                'boolean',
            ],

            'contact_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'contact_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'contact_phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'ticket_types' => [
                'sometimes',
                'array',
            ],

            'ticket_types.*.id' => [
                'nullable',
                'integer',
                'exists:event_ticket_types,id',
            ],

            'ticket_types.*.name' => [
                'required',
                'string',
                'max:255',
            ],

            'ticket_types.*.price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'ticket_types.*.capacity' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        DB::transaction(
            function () use (
                $event,
                $validated
            ) {
                $hasTicketTypes =
                    array_key_exists(
                        'ticket_types',
                        $validated
                    );

                $ticketTypes =
                    $validated['ticket_types']
                    ?? [];

                unset(
                    $validated['ticket_types']
                );

                $hasLocation = array_key_exists('location', $validated);
                $location = $validated['location'] ?? null;
                $settings = collect($validated)->only([
                    'public_registration',
                    'require_approval',
                    'waitlist_enabled',
                    'contact_name',
                    'contact_email',
                    'contact_phone',
                ])->all();

                unset(
                    $validated['location'],
                    $validated['public_registration'],
                    $validated['require_approval'],
                    $validated['waitlist_enabled'],
                    $validated['contact_name'],
                    $validated['contact_email'],
                    $validated['contact_phone'],
                );

                $event->update(
                    $validated
                );

                if ($hasLocation) {
                    $event->eventLocation()->updateOrCreate(
                        ['event_id' => $event->id],
                        ['venue_name' => $location, 'venue_address' => $location]
                    );
                }

                if ($settings) {
                    if (
                        ! empty($settings['public_registration']) &&
                        ! $event->registrationSettings?->public_registration_token
                    ) {
                        $settings['public_registration_token'] = Str::random(48);
                    }

                    $event->registrationSettings()->updateOrCreate(
                        ['event_id' => $event->id],
                        $settings
                    );
                }

                if (! $hasTicketTypes) {
                    return;
                }

                $retainedTicketTypeIds = [];

                foreach (
                    $ticketTypes as $ticketType
                ) {
                    $ticketTypeId =
                        $ticketType['id']
                        ?? null;

                    if ($ticketTypeId) {
                        $existingTicketType =
                            $event
                                ->ticketTypes()
                                ->whereKey(
                                    $ticketTypeId
                                )
                                ->first();

                        if (! $existingTicketType) {
                            abort(
                                422,
                                'One of the ticket types does not belong to this event.'
                            );
                        }

                        $existingTicketType->update([
                            'name' => $ticketType['name'],

                            'price' => $ticketType['price'],

                            'capacity' => $ticketType['capacity'],
                        ]);

                        $retainedTicketTypeIds[] = $existingTicketType->id;

                        continue;
                    }

                    $existingTicketType = $event
                        ->ticketTypes()
                        ->whereRaw('LOWER(TRIM(name)) = ?', [
                            mb_strtolower(trim($ticketType['name'])),
                        ])
                        ->where('price', $ticketType['price'])
                        ->where('capacity', $ticketType['capacity'])
                        ->first();

                    $savedTicketType = $existingTicketType
                        ?? $event->ticketTypes()->create([
                            'name' => $ticketType['name'],

                            'price' => $ticketType['price'],

                            'capacity' => $ticketType['capacity'],
                        ]);

                    $retainedTicketTypeIds[] = $savedTicketType->id;
                }

                $event
                    ->ticketTypes()
                    ->whereNotIn('id', $retainedTicketTypeIds)
                    ->delete();
            }
        );

        $event->refresh();

        $event->load([
            'ticketTypes',
            'eventLocation',
            'registrationSettings',
        ]);

        return response()->json([
            'message' => 'Event updated successfully.',

            'data' => $event,

            'event' => $event,
        ]);
    }
}

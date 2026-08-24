<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventAttendeeController extends Controller
{
    public function index(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->organizer_id ===
                $request->user()->id,
            403
        );

        $attendees = $event
            ->tickets()
            ->with('ticketType')
            ->latest()
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' =>
                    $ticket->id,

                    'name' =>
                    $ticket->attendee_name,

                    'email' =>
                    $ticket->attendee_email,

                    'ticket_type' =>
                    $ticket->ticketType,

                    'ticket_id' =>
                    'TKT-' .
                        str_pad(
                            (string) $ticket->id,
                            6,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'source' =>
                    $ticket->source,

                    'payment_status' =>
                    $ticket->payment_status,

                    'status' =>
                    $ticket->checked_in_at
                        ? 'checked_in'
                        : 'registered',

                    'checked_in_at' =>
                    $ticket->checked_in_at,

                    'created_at' =>
                    $ticket->created_at,
                ];
            });

        return response()->json([
            'data' => $attendees,
        ]);
    }

    public function store(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->organizer_id ===
                $request->user()->id,
            403
        );

        $validated =
            $request->validate([
                'attendee_name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'attendee_email' => [
                    'nullable',
                    'email',
                    'max:255',
                ],

                'event_ticket_type_id' => [
                    'required',
                    'integer',
                    'exists:event_ticket_types,id',
                ],

                'payment_status' => [
                    'nullable',
                    'in:pending,paid,refunded',
                ],
            ]);

        $ticketType =
            $event
            ->ticketTypes()
            ->whereKey(
                $validated['event_ticket_type_id']
            )
            ->first();

        if (!$ticketType) {
            return response()->json([
                'message' =>
                'The selected ticket type does not belong to this event.',
            ], 422);
        }

        $issuedTickets =
            EventTicket::query()
            ->where(
                'event_id',
                $event->id
            )
            ->where(
                'event_ticket_type_id',
                $ticketType->id
            )
            ->count();

        if (
            $ticketType->capacity > 0 &&
            $issuedTickets >=
            $ticketType->capacity
        ) {
            return response()->json([
                'message' =>
                'This ticket type has reached its capacity.',
            ], 422);
        }

        $ticket =
            EventTicket::create([
                'event_id' =>
                $event->id,

                'event_ticket_type_id' =>
                $ticketType->id,

                'attendee_name' =>
                $validated['attendee_name'],

                'attendee_email' =>
                $validated['attendee_email'] ?? null,

                'qr_token' =>
                Str::random(64),

                'source' =>
                'organizer',

                'payment_status' =>
                $validated['payment_status'] ?? 'paid',

                'status' =>
                'valid',

                'checked_in_at' =>
                null,

                'checked_in_by' =>
                null,
            ]);

        $ticket->load(
            'ticketType'
        );

        return response()->json([
            'message' =>
            'Attendee registered successfully.',

            'data' => [
                'id' =>
                $ticket->id,

                'name' =>
                $ticket->attendee_name,

                'email' =>
                $ticket->attendee_email,

                'ticket_type' =>
                $ticket->ticketType,

                'ticket_id' =>
                'TKT-' .
                    str_pad(
                        (string) $ticket->id,
                        6,
                        '0',
                        STR_PAD_LEFT
                    ),

                'qr_token' =>
                $ticket->qr_token,

                'qr_value' =>
                'NASERY:TICKET:' .
                    $ticket->qr_token,

                'source' =>
                $ticket->source,

                'payment_status' =>
                $ticket->payment_status,

                'status' =>
                'registered',

                'checked_in_at' =>
                null,

                'created_at' =>
                $ticket->created_at,
            ],
        ], 201);
    }

    public function summary(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->organizer_id ===
                $request->user()->id,
            403
        );

        $tickets =
            $event->tickets();

        $total =
            (clone $tickets)
            ->count();

        $checkedIn =
            (clone $tickets)
            ->whereNotNull(
                'checked_in_at'
            )
            ->count();

        $walkIns =
            (clone $tickets)
            ->where(
                'source',
                'walk_in'
            )
            ->count();

        $capacity =
            $event
            ->ticketTypes()
            ->sum('capacity');

        return response()->json([
            'data' => [
                'total_registered' =>
                $total,

                'checked_in' =>
                $checkedIn,

                'walk_ins' =>
                $walkIns,

                'capacity' =>
                $capacity,

                'capacity_percent' =>
                $capacity > 0
                    ? min(
                        round(
                            ($total / $capacity) *
                                100
                        ),
                        100
                    )
                    : 0,
            ],
        ]);
    }
}

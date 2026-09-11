<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientEventTicketController extends Controller
{
    public function index(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->client_id ===
                $request->user()->id,
            403
        );

        $tickets =
            $event
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

                    'qr_token' =>
                    $ticket->qr_token,

                    'qr_value' =>
                    'NASERY:TICKET:' .
                        $ticket->qr_token,

                    'source' =>
                    $ticket->source,

                    'attendee_category' =>
                    $ticket->attendee_category,

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
            'data' => $tickets,
        ]);
    }

    public function store(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->client_id ===
                $request->user()->id,
            403
        );

        if (!$event->public_registration) {
            return response()->json([
                'message' =>
                'Registration is currently closed for this event.',
            ], 422);
        }

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
            ->where(
                'status',
                '!=',
                'cancelled'
            )
            ->count();

        if (
            $ticketType->capacity > 0 &&
            $issuedTickets >=
            $ticketType->capacity
        ) {
            return response()->json([
                'message' =>
                'This ticket type is sold out.',
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
                'online',

                'attendee_category' =>
                (float) $ticketType->price > 0
                    ? 'paid'
                    : 'free',

                'payment_status' =>
                (float) $ticketType->price > 0
                    ? 'paid'
                    : null,

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

                'attendee_category' =>
                $ticket->attendee_category,

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

    public function show(
        Request $request,
        Event $event,
        EventTicket $ticket
    ) {
        abort_unless(
            $event->client_id ===
                $request->user()->id,
            403
        );

        abort_unless(
            $ticket->event_id ===
                $event->id,
            404
        );

        $ticket->load(
            'ticketType'
        );

        return response()->json([
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

                'attendee_category' =>
                $ticket->attendee_category,

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
            ],
        ]);
    }
}

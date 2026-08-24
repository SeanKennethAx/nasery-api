<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventQRCheckIn;
use App\Models\EventTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventQRCheckInController extends Controller
{
    public function summary(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->organizer_id === $request->user()->id,
            403
        );

        $registered =
            $event->tickets()
            ->count();

        $checkedIn =
            $event->qrCheckIns()
            ->count();

        $recentCheckIns =
            $event->qrCheckIns()
            ->with([
                'ticket.ticketType',
            ])
            ->latest('checked_in_at')
            ->limit(10)
            ->get()
            ->map(function ($checkIn) {
                return [
                    'id' =>
                    $checkIn->id,

                    'checked_in_at' =>
                    $checkIn->checked_in_at,

                    'ticket' => [
                        'id' =>
                        $checkIn->ticket->id,

                        'attendee_name' =>
                        $checkIn->ticket->attendee_name,

                        'attendee_email' =>
                        $checkIn->ticket->attendee_email,

                        'status' =>
                        $checkIn->ticket->status,

                        'source' =>
                        $checkIn->ticket->source,

                        'payment_status' =>
                        $checkIn->ticket->payment_status,

                        'ticket_type' =>
                        $checkIn->ticket->ticketType,
                    ],
                ];
            });

        return response()->json([
            'data' => [
                'registered' =>
                $registered,

                'checked_in' =>
                $checkedIn,

                'recent_check_ins' =>
                $recentCheckIns,
            ],
        ]);
    }

    public function scan(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->organizer_id === $request->user()->id,
            403
        );

        $validated =
            $request->validate([
                'qr_token' => [
                    'required',
                    'string',
                    'max:255',
                ],
            ]);

        $qrToken =
            trim(
                $validated['qr_token']
            );

        if (
            str_starts_with(
                $qrToken,
                'NASERY:TICKET:'
            )
        ) {
            $qrToken =
                substr(
                    $qrToken,
                    strlen(
                        'NASERY:TICKET:'
                    )
                );
        }

        return DB::transaction(
            function () use (
                $request,
                $event,
                $qrToken
            ) {
                $ticket =
                    EventTicket::query()
                    ->where(
                        'qr_token',
                        $qrToken
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$ticket) {
                    return response()->json([
                        'message' =>
                        'Invalid QR ticket.',

                        'code' =>
                        'INVALID_TICKET',
                    ], 404);
                }

                if (
                    $ticket->event_id !==
                    $event->id
                ) {
                    return response()->json([
                        'message' =>
                        'This ticket belongs to a different event.',

                        'code' =>
                        'WRONG_EVENT',
                    ], 422);
                }

                if (
                    $ticket->status ===
                    'cancelled'
                ) {
                    return response()->json([
                        'message' =>
                        'This ticket has been cancelled.',

                        'code' =>
                        'CANCELLED_TICKET',
                    ], 422);
                }

                $existingCheckIn =
                    EventQRCheckIn::query()
                    ->where(
                        'event_ticket_id',
                        $ticket->id
                    )
                    ->first();

                if ($existingCheckIn) {
                    $ticket->load(
                        'ticketType'
                    );

                    return response()->json([
                        'message' =>
                        'This ticket has already been checked in.',

                        'code' =>
                        'ALREADY_CHECKED_IN',

                        'data' =>
                        $this->formatTicket(
                            $ticket
                        ),
                    ], 409);
                }

                $checkedInAt =
                    now();

                $ticket->update([
                    'checked_in_at' =>
                    $checkedInAt,

                    'checked_in_by' =>
                    $request->user()->id,

                    'status' =>
                    'used',
                ]);

                EventQRCheckIn::create([
                    'event_id' =>
                    $event->id,

                    'event_ticket_id' =>
                    $ticket->id,

                    'checked_in_by' =>
                    $request->user()->id,

                    'checked_in_at' =>
                    $checkedInAt,
                ]);

                $ticket->load(
                    'ticketType'
                );

                return response()->json([
                    'message' =>
                    'Attendee checked in successfully.',

                    'code' =>
                    'CHECKED_IN',

                    'data' =>
                    $this->formatTicket(
                        $ticket
                    ),
                ]);
            }
        );
    }

    public function manual(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->organizer_id === $request->user()->id,
            403
        );

        $validated =
            $request->validate([
                'ticket_id' => [
                    'required',
                    'integer',
                ],
            ]);

        return DB::transaction(
            function () use (
                $request,
                $event,
                $validated
            ) {
                $ticket =
                    EventTicket::query()
                    ->where(
                        'id',
                        $validated['ticket_id']
                    )
                    ->where(
                        'event_id',
                        $event->id
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$ticket) {
                    return response()->json([
                        'message' =>
                        'Ticket could not be found for this event.',

                        'code' =>
                        'INVALID_TICKET',
                    ], 404);
                }

                if (
                    $ticket->status ===
                    'cancelled'
                ) {
                    return response()->json([
                        'message' =>
                        'This ticket has been cancelled.',

                        'code' =>
                        'CANCELLED_TICKET',
                    ], 422);
                }

                if (
                    $ticket->checked_in_at
                ) {
                    $ticket->load(
                        'ticketType'
                    );

                    return response()->json([
                        'message' =>
                        'This attendee has already been checked in.',

                        'code' =>
                        'ALREADY_CHECKED_IN',

                        'data' =>
                        $this->formatTicket(
                            $ticket
                        ),
                    ], 409);
                }

                $checkedInAt =
                    now();

                $ticket->update([
                    'checked_in_at' =>
                    $checkedInAt,

                    'checked_in_by' =>
                    $request->user()->id,

                    'status' =>
                    'used',
                ]);

                $ticket->load(
                    'ticketType'
                );

                return response()->json([
                    'message' =>
                    'Attendee checked in manually.',

                    'code' =>
                    'MANUAL_CHECKED_IN',

                    'data' =>
                    $this->formatTicket(
                        $ticket
                    ),
                ]);
            }
        );
    }

    private function formatTicket(
        EventTicket $ticket
    ): array {
        return [
            'id' =>
            $ticket->id,

            'event_id' =>
            $ticket->event_id,

            'attendee_name' =>
            $ticket->attendee_name,

            'attendee_email' =>
            $ticket->attendee_email,

            'status' =>
            $ticket->status,

            'source' =>
            $ticket->source,

            'payment_status' =>
            $ticket->payment_status,

            'checked_in_at' =>
            $ticket->checked_in_at,

            'ticket_type' =>
            $ticket->ticketType,
        ];
    }
}

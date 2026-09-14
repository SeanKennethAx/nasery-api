<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventCheckInController extends Controller
{
    public function summary(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->isManagedBy($request->user()),
            403
        );

        $registered =
            $event->tickets()
                ->count();

        $checkedIn =
            $event->tickets()
                ->whereNotNull(
                    'checked_in_at'
                )
                ->count();

        $recent =
            $event->tickets()
                ->with('ticketType')
                ->whereNotNull(
                    'checked_in_at'
                )
                ->latest('checked_in_at')
                ->limit(10)
                ->get();

        return response()->json([
            'data' => [
                'registered' => $registered,

                'checked_in' => $checkedIn,

                'recent_check_ins' => $recent,
            ],
        ]);
    }

    public function scan(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->isManagedBy($request->user()),
            403
        );

        $validated =
            $request->validate([
                'qr_token' => [
                    'required',
                    'string',
                ],
            ]);
        $token =
            str_replace(
                'NASERY:TICKET:',
                '',
                trim(
                    $validated['qr_token']
                )
            );

        return DB::transaction(
            function () use (
                $event,
                $token,
                $request
            ) {
                $ticket =
                    EventTicket::query()
                        ->where(
                            'qr_token',
                            $token
                        )
                        ->lockForUpdate()
                        ->first();

                if (! $ticket) {
                    return response()->json([
                        'message' => 'Invalid QR ticket.',

                        'code' => 'INVALID_TICKET',
                    ], 404);
                }

                if (
                    $ticket->event_id !==
                    $event->id
                ) {
                    return response()->json([
                        'message' => 'This ticket belongs to another event.',

                        'code' => 'WRONG_EVENT',
                    ], 422);
                }

                if (
                    $ticket->status ===
                    'cancelled'
                ) {
                    return response()->json([
                        'message' => 'This ticket has been cancelled.',

                        'code' => 'CANCELLED_TICKET',
                    ], 422);
                }

        if (in_array($ticket->status, ['pending_approval', 'payment_pending'], true)) {
                    return response()->json([
                        'message' => 'This registration is still awaiting organizer approval.',
                        'code' => 'PENDING_APPROVAL',
                    ], 422);
                }
                if (
                    $ticket->checked_in_at
                ) {
                    $ticket->load(
                        'ticketType'
                    );

                    return response()->json([
                        'message' => 'This attendee is already checked in.',

                        'code' => 'ALREADY_CHECKED_IN',

                        'data' => $ticket,
                    ], 409);
                }
                $ticket->update([
                    'checked_in_at' => now(),

                    'checked_in_by' => $request->user()->id,

                    'status' => 'used',
                ]);

                $ticket->load(
                    'ticketType'
                );

                return response()->json([
                    'message' => 'Check-in successful.',

                    'code' => 'CHECKED_IN',

                    'data' => $ticket,
                ]);
            }
        );
    }

    public function manual(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->isManagedBy($request->user()),
            403
        );

        $validated =
            $request->validate([
                'ticket_id' => [
                    'required',
                    'integer',
                ],
            ]);

        $ticket =
            $event->tickets()
                ->whereKey(
                    $validated['ticket_id']
                )
                ->firstOrFail();

        if (
            $ticket->checked_in_at
        ) {
            return response()->json([
                'message' => 'This attendee is already checked in.',
            ], 409);
        }

        $ticket->update([
            'checked_in_at' => now(),

            'checked_in_by' => $request->user()->id,

            'status' => 'used',
        ]);

        return response()->json([
            'message' => 'Manual check-in successful.',

            'data' => $ticket->load(
                'ticketType'
            ),
        ]);
    }
}

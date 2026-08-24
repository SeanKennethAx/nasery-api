<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventTicket;
use App\Models\EventWalkIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventWalkInController extends Controller
{
    public function index(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->organizer_id === $request->user()->id,
            403
        );

        $walkIns = $event
            ->walkIns()
            ->with('ticketType')
            ->latest()
            ->get();

        return response()->json([
            'data' => $walkIns,
        ]);
    }

    public function summary(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->organizer_id === $request->user()->id,
            403
        );

        $pending =
            $event->walkIns()
            ->where(
                'payment_status',
                'pending'
            )
            ->count();

        $paid =
            $event->walkIns()
            ->where(
                'payment_status',
                'paid'
            )
            ->count();

        $capacity =
            $event->ticketTypes()
            ->sum('capacity');

        $registered =
            $event->tickets()
            ->count();

        $remaining =
            max(
                $capacity - $registered,
                0
            );

        return response()->json([
            'data' => [
                'pending' => $pending,
                'paid' => $paid,
                'capacity' => $capacity,
                'registered' => $registered,
                'remaining' => $remaining,
            ],
        ]);
    }

    public function store(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->organizer_id === $request->user()->id,
            403
        );

        $validated =
            $request->validate([
                'event_ticket_type_id' => [
                    'required',
                    'integer',
                    'exists:event_ticket_types,id',
                ],

                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                ],

                'phone' => [
                    'nullable',
                    'string',
                    'max:50',
                ],
            ]);

        $ticketType =
            $event->ticketTypes()
            ->whereKey(
                $validated['event_ticket_type_id']
            )
            ->firstOrFail();

        $sold =
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
            $sold >=
            $ticketType->capacity
        ) {
            return response()->json([
                'message' =>
                'This ticket type is already sold out.',
            ], 422);
        }

        $walkIn =
            $event->walkIns()
            ->create([
                'event_ticket_type_id' =>
                $ticketType->id,

                'name' =>
                $validated['name'],

                'email' =>
                $validated['email'] ??
                    null,

                'phone' =>
                $validated['phone'] ??
                    null,

                'amount' =>
                $ticketType->price,

                'payment_status' =>
                'pending',
            ]);

        $walkIn->load(
            'ticketType'
        );

        return response()->json([
            'message' =>
            'Walk-in added successfully.',

            'data' =>
            $walkIn,
        ], 201);
    }

    public function processPayment(
        Request $request,
        Event $event,
        EventWalkIn $walkIn
    ) {
        abort_unless(
            $event->organizer_id === $request->user()->id,
            403
        );

        abort_unless(
            $walkIn->event_id === $event->id,
            404
        );

        if (
            $walkIn->payment_status ===
            'paid'
        ) {
            return response()->json([
                'message' =>
                'This walk-in has already been paid.',
            ], 409);
        }

        return DB::transaction(
            function () use (
                $event,
                $walkIn
            ) {
                $walkIn->load(
                    'ticketType'
                );

                $ticketType =
                    $walkIn->ticketType;

                if (!$ticketType) {
                    return response()->json([
                        'message' =>
                        'Ticket type could not be found.',
                    ], 422);
                }

                $sold =
                    EventTicket::query()
                    ->where(
                        'event_id',
                        $event->id
                    )
                    ->where(
                        'event_ticket_type_id',
                        $ticketType->id
                    )
                    ->lockForUpdate()
                    ->count();

                if (
                    $sold >=
                    $ticketType->capacity
                ) {
                    return response()->json([
                        'message' =>
                        'This ticket type is already sold out.',
                    ], 422);
                }

                $walkIn->update([
                    'payment_status' =>
                    'paid',

                    'paid_at' =>
                    now(),
                ]);

                $ticket =
                    EventTicket::create([
                        'event_id' =>
                        $event->id,

                        'event_ticket_type_id' =>
                        $ticketType->id,

                        'attendee_name' =>
                        $walkIn->name,

                        'attendee_email' =>
                        $walkIn->email,

                        'qr_token' =>
                        Str::random(64),

                        'source' =>
                        'walk_in',

                        'payment_status' =>
                        'paid',

                        'status' =>
                        'valid',
                    ]);

                return response()->json([
                    'message' =>
                    'Payment processed and ticket issued successfully.',

                    'data' => [
                        'walk_in' =>
                        $walkIn->fresh(
                            'ticketType'
                        ),

                        'ticket' =>
                        $ticket,
                    ],
                ]);
            }
        );
    }

    public function destroy(
        Request $request,
        Event $event,
        EventWalkIn $walkIn
    ) {
        abort_unless(
            $event->organizer_id === $request->user()->id,
            403
        );

        abort_unless(
            $walkIn->event_id === $event->id,
            404
        );

        if (
            $walkIn->payment_status ===
            'paid'
        ) {
            return response()->json([
                'message' =>
                'A paid walk-in cannot be removed.',
            ], 422);
        }

        $walkIn->delete();

        return response()->json([
            'message' =>
            'Walk-in removed successfully.',
        ]);
    }
}

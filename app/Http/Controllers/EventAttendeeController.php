<?php

namespace App\Http\Controllers;

use App\Mail\EventTicketMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventTicket;
use App\Models\QrTicket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class EventAttendeeController extends Controller
{
    public function index(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->isManagedBy($request->user()),
            403
        );

        $attendees = $event
            ->tickets()
            ->with('ticketType')
            ->latest()
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,

                    'name' => $ticket->attendee_name,

                    'email' => $ticket->attendee_email,

                    'ticket_type' => $ticket->ticketType,

                    'ticket_id' => 'TKT-'.
                        str_pad(
                            (string) $ticket->id,
                            6,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'source' => $ticket->source,

                    'attendee_category' => $ticket->attendee_category,

                    'payment_status' => $ticket->payment_status,

                    'status' => $ticket->checked_in_at
                        ? 'checked_in'
                        : ($ticket->status === 'pending_approval'
                            ? 'pending_approval'
                            : ($ticket->status === 'cancelled'
                                ? 'rejected'
                                : 'registered')),

                    'checked_in_at' => $ticket->checked_in_at,

                    'created_at' => $ticket->created_at,
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
            $event->isManagedBy($request->user()),
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

                'attendee_category' => [
                    'required',
                    'in:invited,free,paid',
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

        if (! $ticketType) {
            return response()->json([
                'message' => 'The selected ticket type does not belong to this event.',
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
                'message' => 'This ticket type has reached its capacity.',
            ], 422);
        }

        $ticket =
            EventTicket::create([
                'event_id' => $event->id,

                'event_ticket_type_id' => $ticketType->id,

                'attendee_name' => $validated['attendee_name'],

                'attendee_email' => $validated['attendee_email'] ?? null,

                'qr_token' => Str::random(64),

                'source' => 'organizer',

                'attendee_category' => $validated['attendee_category'],

                'payment_status' => $validated['attendee_category'] === 'paid'
                    ? ($validated['payment_status'] ?? 'pending')
                    : null,

                'status' => 'valid',

                'checked_in_at' => null,

                'checked_in_by' => null,
            ]);

        $ticket->load(
            'ticketType'
        );

        return response()->json([
            'message' => 'Attendee registered successfully.',

            'data' => [
                'id' => $ticket->id,

                'name' => $ticket->attendee_name,

                'email' => $ticket->attendee_email,

                'ticket_type' => $ticket->ticketType,

                'ticket_id' => 'TKT-'.
                    str_pad(
                        (string) $ticket->id,
                        6,
                        '0',
                        STR_PAD_LEFT
                    ),

                'qr_token' => $ticket->qr_token,

                'qr_value' => 'NASERY:TICKET:'.
                    $ticket->qr_token,

                'source' => $ticket->source,

                'attendee_category' => $ticket->attendee_category,

                'payment_status' => $ticket->payment_status,

                'status' => 'registered',

                'checked_in_at' => null,

                'created_at' => $ticket->created_at,
            ],
        ], 201);
    }

    public function summary(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->isManagedBy($request->user()),
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
                'total_registered' => $total,

                'checked_in' => $checkedIn,

                'walk_ins' => $walkIns,

                'capacity' => $capacity,

                'capacity_percent' => $capacity > 0
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

    public function approve(Request $request, Event $event, EventTicket $ticket)
    {
        abort_unless($event->isManagedBy($request->user()), 403);
        abort_unless($ticket->event_id === $event->id, 404);

        if ($ticket->status !== 'pending_approval') {
            return response()->json(['message' => 'This registration is no longer pending approval.'], 422);
        }

        DB::transaction(function () use ($ticket) {
            $ticket->update(['status' => 'valid']);

            $qrTicket = QrTicket::query()->where('qr_token', $ticket->qr_token)->lockForUpdate()->first();
            $qrTicket?->update(['generated_at' => now()]);

            if ($qrTicket) {
                EventRegistration::query()
                    ->whereKey($qrTicket->registration_id)
                    ->update(['status' => 'registered']);
            }
        });

        $emailSent = false;

        if ($ticket->attendee_email) {
            try {
                $ticket->load(['event', 'ticketType']);
                $ticketId = $ticket->ticket_code;
                $qrValue = $ticket->qr_value;
                $qr = QrCode::format('png')->size(400)->margin(1)->generate($qrValue);
                $png = $qr instanceof HtmlString ? $qr->toHtml() : (string) $qr;

                $pdfContent = Pdf::loadView('tickets.pdf', [
                    'ticket' => $ticket,
                    'event' => $ticket->event,
                    'ticketType' => $ticket->ticketType,
                    'ticketId' => $ticketId,
                    'qrValue' => $qrValue,
                    'qrBase64' => base64_encode($png),
                ])->setPaper('a4', 'portrait')->output();

                Mail::to($ticket->attendee_email)->send(new EventTicketMail($ticket, $pdfContent));
                QrTicket::query()->where('qr_token', $ticket->qr_token)->update(['emailed_at' => now()]);
                $emailSent = true;
            } catch (Throwable $error) {
                report($error);
            }
        }

        return response()->json([
            'message' => $emailSent
                ? 'Registration approved and the QR ticket was emailed to the attendee.'
                : 'Registration approved. The QR ticket is now active.',
        ]);
    }

    public function reject(Request $request, Event $event, EventTicket $ticket)
    {
        abort_unless($event->isManagedBy($request->user()), 403);
        abort_unless($ticket->event_id === $event->id, 404);

        if ($ticket->status !== 'pending_approval') {
            return response()->json(['message' => 'This registration is no longer pending approval.'], 422);
        }

        DB::transaction(function () use ($ticket) {
            $ticket->update(['status' => 'cancelled']);
            $qrTicket = QrTicket::query()->where('qr_token', $ticket->qr_token)->lockForUpdate()->first();

            if ($qrTicket) {
                EventRegistration::query()
                    ->whereKey($qrTicket->registration_id)
                    ->update(['status' => 'rejected']);
            }
        });

        return response()->json(['message' => 'Registration request rejected.']);
    }
}

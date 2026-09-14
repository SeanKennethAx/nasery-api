<?php

namespace App\Http\Controllers;

use App\Mail\EventTicketMail;
use App\Models\EventPayment;
use App\Models\EventTicket;
use App\Models\QrTicket;
use App\Services\PayMongoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class PublicEventPaymentController extends Controller
{
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Paymongo-Signature');
        $secret = (string) config('services.paymongo.webhook_secret');

        if (! $this->validSignature($payload, $signature, $secret)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        $event = json_decode($payload, true);
        $type = $event['data']['attributes']['type'] ?? null;
        $sessionId = $event['data']['attributes']['data']['id'] ?? null;

        if ($type === 'checkout_session.payment.paid' && is_string($sessionId)) {
            $payment = EventPayment::query()->where('checkout_session_id', $sessionId)->first();

            if ($payment && $payment->status !== 'paid') {
                $payment->update(['provider_payload' => $event]);
                $this->complete($payment);
            }
        }

        return response()->json(['received' => true]);
    }

    public function show(string $reference, PayMongoService $payMongo): JsonResponse
    {
        $payment = EventPayment::query()->where('reference', $reference)->firstOrFail();

        if ($payment->status !== 'paid' && $payment->checkout_session_id) {
            try {
                $session = $payMongo->retrieveCheckoutSession($payment->checkout_session_id);

                $payment->update(['provider_payload' => $session]);

                if ($payMongo->isPaid($session)) {
                    $this->complete($payment);
                    $payment->refresh();
                }
            } catch (Throwable $error) {
                report($error);
            }
        }

        $tickets = $payment->status === 'paid'
            ? EventTicket::query()
                ->whereIn('id', $payment->event_ticket_ids)
                ->where('status', 'valid')
                ->with('ticketType')
                ->get()
                ->map(fn (EventTicket $ticket) => [
                    'id' => $ticket->id,
                    'ticket_id' => 'TKT-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT),
                    'name' => $ticket->attendee_name,
                    'email' => $ticket->attendee_email,
                    'ticket_type' => $ticket->ticketType,
                    'qr_token' => $ticket->qr_token,
                    'qr_value' => 'NASERY:TICKET:'.$ticket->qr_token,
                    'source' => $ticket->source,
                    'attendee_category' => $ticket->attendee_category,
                    'payment_status' => $ticket->payment_status,
                    'status' => $ticket->status,
                ])->values()
            : [];

        return response()->json(['data' => [
            'reference' => $payment->reference,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'tickets' => $tickets,
        ]]);
    }

    private function complete(EventPayment $payment): void
    {
        $ticketsToEmail = DB::transaction(function () use ($payment) {
            $locked = EventPayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($locked->status === 'paid') {
                return collect();
            }

            $tickets = EventTicket::query()
                ->whereIn('id', $locked->event_ticket_ids)
                ->with(['event', 'ticketType'])
                ->get();

            foreach ($tickets as $ticket) {
                $requiresApproval = (bool) $ticket->event->require_approval;

                if ($ticket->attendee_category !== 'paid') {
                    continue;
                }

                $ticket->update([
                    'payment_status' => 'paid',
                    'status' => $requiresApproval ? 'pending_approval' : 'valid',
                ]);

                $qrTicket = QrTicket::query()->where('qr_token', $ticket->qr_token)->first();
                $registration = $qrTicket?->registration;

                $registration?->update([
                    'payment_status' => 'paid',
                    'status' => $requiresApproval ? 'pending_approval' : 'registered',
                ]);

                if (! $requiresApproval) {
                    $qrTicket?->update(['generated_at' => now()]);
                }
            }

            $locked->update(['status' => 'paid', 'paid_at' => now()]);

            return $tickets->filter(
                fn (EventTicket $ticket) => $ticket->attendee_category === 'paid' &&
                    ! $ticket->event->require_approval
            );
        });

        foreach ($ticketsToEmail as $ticket) {
            if (! $ticket->attendee_email) {
                continue;
            }

            try {
                $qrValue = 'NASERY:TICKET:'.$ticket->qr_token;
                $qr = QrCode::format('png')->size(400)->margin(1)->generate($qrValue);
                $qrBase64 = base64_encode($qr instanceof HtmlString ? $qr->toHtml() : (string) $qr);
                $ticketId = 'TKT-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT);
                $pdf = Pdf::loadView('tickets.pdf', [
                    'ticket' => $ticket,
                    'event' => $ticket->event,
                    'ticketType' => $ticket->ticketType,
                    'ticketId' => $ticketId,
                    'qrValue' => $qrValue,
                    'qrBase64' => $qrBase64,
                ])->setPaper('a4', 'portrait')->output();

                Mail::to($ticket->attendee_email)->send(new EventTicketMail($ticket, $pdf));
                QrTicket::query()->where('qr_token', $ticket->qr_token)->update(['emailed_at' => now()]);
            } catch (Throwable $error) {
                report($error);
            }
        }
    }

    private function validSignature(string $payload, string $header, string $secret): bool
    {
        if ($secret === '' || $header === '') {
            return false;
        }

        $parts = collect(explode(',', $header))
            ->mapWithKeys(function (string $part) {
                [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');

                return [$key => $value];
            });

        $timestamp = $parts->get('t');
        $signature = $parts->get('te') ?: $parts->get('li');

        if (! $timestamp || ! $signature || abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        return hash_equals(
            (string) $signature,
            hash_hmac('sha256', $timestamp.'.'.$payload, $secret)
        );
    }
}

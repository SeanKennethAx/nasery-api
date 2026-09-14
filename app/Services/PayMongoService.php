<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventPayment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayMongoService
{
    private const BASE_URL = 'https://api.paymongo.com/v1';

    public function isConfigured(): bool
    {
        $secret = (string) config('services.paymongo.secret_key');

        return $secret !== '' && ! str_contains($secret, 'your_secret_key');
    }

    public function createCheckoutSession(EventPayment $payment, Event $event, array $items, array $billing): array
    {
        $response = $this->client()->post(self::BASE_URL.'/checkout_sessions', [
            'data' => [
                'attributes' => [
                    'billing' => array_filter($billing),
                    'cancel_url' => $this->redirectUrl('cancel_url', $payment),
                    'description' => 'Secure attendee registration for '.$event->name.'. Your QR ticket will be released after payment confirmation.',
                    'line_items' => $items,
                    'merchant' => 'NaSeRy Event Management',
                    'payment_method_types' => ['card', 'gcash', 'grab_pay', 'paymaya'],
                    'reference_number' => 'NSR-'.$event->id.'-'.$payment->id,
                    'send_email_receipt' => true,
                    'show_description' => true,
                    'show_line_items' => true,
                    'success_url' => $this->redirectUrl('success_url', $payment),
                ],
            ],
        ])->throw()->json('data');

        if (! is_array($response) || empty($response['id']) || empty($response['attributes']['checkout_url'])) {
            throw new RuntimeException('PayMongo did not return a checkout URL.');
        }

        return $response;
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        $data = $this->client()
            ->get(self::BASE_URL.'/checkout_sessions/'.$sessionId)
            ->throw()
            ->json('data');

        if (! is_array($data)) {
            throw new RuntimeException('PayMongo returned an invalid checkout session.');
        }

        return $data;
    }

    public function isPaid(array $session): bool
    {
        $attributes = $session['attributes'] ?? [];

        if (($attributes['payment_intent']['attributes']['status'] ?? null) === 'succeeded') {
            return true;
        }

        foreach (($attributes['payments'] ?? []) as $payment) {
            if (($payment['attributes']['status'] ?? null) === 'paid') {
                return true;
            }
        }

        return false;
    }

    private function client(): PendingRequest
    {
        $secret = (string) config('services.paymongo.secret_key');

        if (! $this->isConfigured()) {
            throw new RuntimeException('PayMongo is not configured. Add a valid PAYMONGO_SECRET_KEY.');
        }

        return Http::withBasicAuth($secret, '')
            ->acceptJson()
            ->asJson()
            ->timeout(20);
    }

    private function redirectUrl(string $key, EventPayment $payment): string
    {
        $url = (string) config('services.paymongo.'.$key);
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'payment_reference='.rawurlencode($payment->reference);
    }
}

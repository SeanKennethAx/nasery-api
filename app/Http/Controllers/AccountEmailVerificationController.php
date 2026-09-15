<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerificationCodeMail;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class AccountEmailVerificationController extends Controller
{
    public const SCOPE = 'account-registration';

    public function send(Request $request): JsonResponse
    {
        $email = $this->validatedEmail($request);

        if (User::query()->where('email', $email)->exists()) {
            return response()->json(['message' => 'This email address is already registered.'], 422);
        }

        $verification = EmailVerification::query()
            ->where('email', $email)
            ->where('event_token', self::SCOPE)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if ($verification?->last_sent_at?->gt(now()->subSeconds(60))) {
            return response()->json([
                'message' => 'Please wait before requesting another verification code.',
                'data' => ['resend_in' => max(1, 60 - $verification->last_sent_at->diffInSeconds(now()))],
            ], 429);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        if ($verification) {
            $verification->resetForNewCode(Hash::make($code));
            $verification->refresh();
        } else {
            $verification = EmailVerification::create([
                'email' => $email,
                'event_token' => self::SCOPE,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(10),
                'last_sent_at' => now(),
                'attempts' => 0,
            ]);
        }

        try {
            Mail::to($email)->send(new EmailVerificationCodeMail($code, 'your NaSeRy account', 'account'));
        } catch (Throwable $error) {
            report($error);
            $verification->delete();

            return response()->json(['message' => 'Unable to send the verification email. Please try again.'], 500);
        }

        return response()->json([
            'message' => 'A 6-digit verification code was sent to your email.',
            'data' => ['expires_in' => 600, 'resend_in' => 60],
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $email = $this->validatedEmail($request);
        $validated = $request->validate(['code' => ['required', 'digits:6']]);

        try {
            $verification = DB::transaction(function () use ($email, $validated) {
                $verification = EmailVerification::query()
                    ->where('email', $email)
                    ->where('event_token', self::SCOPE)
                    ->whereNull('used_at')
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if (! $verification) {
                    abort(422, 'Request a verification code before continuing.');
                }
                if ($verification->isExpired()) {
                    abort(422, 'The verification code has expired. Request a new code.');
                }
                if ($verification->hasTooManyAttempts()) {
                    abort(429, 'Too many invalid attempts. Request a new code.');
                }

                if (! Hash::check((string) $validated['code'], $verification->code_hash)) {
                    $verification->recordFailedAttempt();
                    abort(422, 'The verification code is incorrect.');
                }

                $verification->markAsVerified(Str::random(64));

                return $verification->fresh();
            });
        } catch (HttpException $error) {
            return response()->json(['message' => $error->getMessage()], $error->getStatusCode());
        }

        return response()->json([
            'message' => 'Email verified successfully.',
            'data' => ['verified' => true, 'verification_token' => $verification->verification_token],
        ]);
    }

    private function validatedEmail(Request $request): string
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        return strtolower(trim($validated['email']));
    }
}

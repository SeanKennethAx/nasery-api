<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerificationCodeMail;
use App\Mail\EventTicketMail;
use App\Models\Attendee;
use App\Models\EmailVerification;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventTicket;
use App\Models\QrTicket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class PublicEventRegistrationController extends Controller
{
    public function show(
        string $token
    ): JsonResponse {
        $event = Event::query()
            ->whereHas(
                'registrationSettings',
                fn ($query) => $query->where('public_registration_token', $token)
            )
            ->with([
                'ticketTypes',
                'organizer',
                'eventLocation',
                'registrationSettings',
            ])
            ->first();

        if (!$event) {
            return response()->json([
                'message' =>
                'Registration link not found.',
            ], 404);
        }

        if (!$event->public_registration) {
            return response()->json([
                'message' =>
                'Registration is currently closed for this event.',

                'registration_open' =>
                false,
            ], 422);
        }

        $ticketTypes = $event
            ->ticketTypes
            ->map(
                function ($ticketType) use ($event) {
                    $issued =
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

                    $remaining =
                        $ticketType->capacity > 0
                        ? max(
                            $ticketType->capacity -
                                $issued,
                            0
                        )
                        : null;

                    $soldOut =
                        $ticketType->capacity > 0 &&
                        $issued >=
                        $ticketType->capacity;

                    return [
                        'id' =>
                        $ticketType->id,

                        'name' =>
                        $ticketType->name,

                        'price' =>
                        $ticketType->price,

                        'capacity' =>
                        $ticketType->capacity,

                        'issued' =>
                        $issued,

                        'remaining' =>
                        $remaining,

                        'sold_out' =>
                        $soldOut,
                    ];
                }
            )
            ->values();

        return response()->json([
            'registration_open' =>
            true,

            'data' => [
                'event' => [
                    'id' =>
                    $event->id,

                    'name' =>
                    $event->name,

                    'event_type' =>
                    $event->event_type,

                    'description' =>
                    $event->description,

                    'event_date' =>
                    $event->event_date,

                    'location' =>
                    $event->location,

                    'start_time' =>
                    $event->start_time,

                    'end_time' =>
                    $event->end_time,

                    'public_registration' =>
                    (bool) $event->public_registration,

                    'require_approval' =>
                    (bool) $event->require_approval,

                    'waitlist_enabled' =>
                    (bool) $event->waitlist_enabled,

                    'contact_name' =>
                    $event->contact_name,

                    'contact_email' =>
                    $event->contact_email,

                    'contact_phone' =>
                    $event->contact_phone,

                    'ticket_types' =>
                    $ticketTypes,
                ],
            ],
        ]);
    }

    public function sendEmailVerificationCode(
        Request $request,
        string $token
    ): JsonResponse {
        $event =
            Event::query()
            ->whereHas(
                'registrationSettings',
                fn ($query) => $query->where('public_registration_token', $token)
            )
            ->with('registrationSettings')
            ->first();

        if (!$event) {
            return response()->json([
                'message' =>
                'Registration link not found.',
            ], 404);
        }

        if (!$event->public_registration) {
            return response()->json([
                'message' =>
                'Registration is currently closed for this event.',
            ], 422);
        }

        $validated =
            $request->validate([
                'email' => [
                    'required',
                    'email',
                    'max:255',
                ],
            ], [
                'email.required' =>
                'The email address is required.',

                'email.email' =>
                'Please provide a valid email address.',
            ]);

        $email =
            strtolower(
                trim(
                    $validated['email']
                )
            );

        /*
         * Keep only one active verification row for an email/event.
         * Historical rows with used_at remain untouched.
         */
        $existing =
            EmailVerification::query()
            ->where(
                'email',
                $email
            )
            ->where(
                'event_token',
                $token
            )
            ->whereNull(
                'used_at'
            )
            ->latest('id')
            ->first();

        if (
            $existing &&
            $existing->last_sent_at &&
            $existing->last_sent_at
            ->gt(
                now()->subSeconds(60)
            )
        ) {
            $elapsed =
                $existing
                ->last_sent_at
                ->diffInSeconds(
                    now()
                );

            $remaining =
                max(
                    1,
                    60 - $elapsed
                );

            return response()->json([
                'message' =>
                'Please wait before requesting another verification code.',

                'data' => [
                    'resend_in' =>
                    $remaining,
                ],
            ], 429);
        }

        $code =
            str_pad(
                (string) random_int(
                    0,
                    999999
                ),
                6,
                '0',
                STR_PAD_LEFT
            );

        $codeHash =
            Hash::make(
                $code
            );

        /*
         * Reuse the same ACTIVE row when resending.
         *
         * This avoids delete/recreate timing issues and guarantees
         * that the PIN sent by this request is the PIN stored in the
         * active verification row.
         */
        if ($existing) {
            $existing->resetForNewCode(
                $codeHash
            );

            $verification =
                $existing->fresh();
        } else {
            $verification =
                EmailVerification::create([
                    'email' =>
                    $email,

                    'event_token' =>
                    $token,

                    'code_hash' =>
                    $codeHash,

                    'verification_token' =>
                    null,

                    'expires_at' =>
                    now()->addMinutes(10),

                    'verified_at' =>
                    null,

                    'used_at' =>
                    null,

                    'last_sent_at' =>
                    now(),

                    'attempts' =>
                    0,
                ]);
        }

        /*
         * Safety cleanup in case older active rows exist from
         * earlier versions of the application.
         */
        EmailVerification::query()
            ->where(
                'email',
                $email
            )
            ->where(
                'event_token',
                $token
            )
            ->whereNull(
                'used_at'
            )
            ->whereKeyNot(
                $verification->getKey()
            )
            ->delete();

        try {
            Mail::to(
                $email
            )->send(
                new EmailVerificationCodeMail(
                    $code,
                    $event->name
                )
            );
        } catch (Throwable $error) {
            report($error);

            /*
             * Do not leave a PIN active when the email itself failed.
             */
            $verification->delete();

            return response()->json([
                'message' =>
                'Unable to send the verification email. Please try again.',
            ], 500);
        }

        /*
         * LOCAL DEVELOPMENT ONLY:
         * Uncomment temporarily if you need to compare the exact
         * generated PIN with the email being received.
         *
         * logger()->debug('NaSeRy verification PIN', [
         *     'verification_id' => $verification->id,
         *     'email' => $email,
         *     'code' => $code,
         * ]);
         *
         * Never log verification PINs in production.
         */

        return response()->json([
            'message' =>
            $existing
                ? 'A new 6-digit verification code was sent. Any previous code is now invalid.'
                : 'A 6-digit verification code was sent to your email.',

            'data' => [
                'verification_id' =>
                $verification->id,

                'expires_in' =>
                600,

                'resend_in' =>
                60,
            ],
        ]);
    }

    public function verifyEmailVerificationCode(
        Request $request,
        string $token
    ): JsonResponse {
        $event =
            Event::query()
            ->whereHas(
                'registrationSettings',
                fn ($query) => $query->where('public_registration_token', $token)
            )
            ->with('registrationSettings')
            ->first();

        if (!$event) {
            return response()->json([
                'message' =>
                'Registration link not found.',
            ], 404);
        }

        if (!$event->public_registration) {
            return response()->json([
                'message' =>
                'Registration is currently closed for this event.',
            ], 422);
        }

        $validated =
            $request->validate([
                'email' => [
                    'required',
                    'email',
                    'max:255',
                ],

                'code' => [
                    'required',
                    'digits:6',
                ],
            ], [
                'email.required' =>
                'The email address is required.',

                'email.email' =>
                'Please provide a valid email address.',

                'code.required' =>
                'The 6-digit verification code is required.',

                'code.digits' =>
                'The verification code must contain exactly 6 digits.',
            ]);

        $email =
            strtolower(
                trim(
                    $validated['email']
                )
            );

        $code =
            (string) $validated['code'];

        try {
            $result =
                DB::transaction(
                    function () use (
                        $email,
                        $token,
                        $code
                    ) {
                        /*
                         * Lock the active verification row while checking the
                         * PIN so two requests cannot verify/modify it at once.
                         */
                        $verification =
                            EmailVerification::query()
                            ->where(
                                'email',
                                $email
                            )
                            ->where(
                                'event_token',
                                $token
                            )
                            ->whereNull(
                                'used_at'
                            )
                            ->latest('id')
                            ->lockForUpdate()
                            ->first();

                        if (!$verification) {
                            abort(
                                422,
                                'No active verification request was found for this email. Please request a new code.'
                            );
                        }

                        if (
                            $verification->isVerified()
                        ) {
                            return [
                                'message' =>
                                'This email has already been verified.',

                                'verification' =>
                                $verification,
                            ];
                        }

                        if (
                            $verification->isExpired()
                        ) {
                            abort(
                                422,
                                'The verification code has expired. Please request a new code.'
                            );
                        }

                        if (
                            $verification
                            ->hasTooManyAttempts()
                        ) {
                            abort(
                                429,
                                'Too many invalid verification attempts. Please request a new code.'
                            );
                        }

                        if (
                            !Hash::check(
                                $code,
                                $verification->code_hash
                            )
                        ) {
                            $verification
                                ->recordFailedAttempt();

                            abort(
                                422,
                                'The verification code is incorrect. Make sure you are using the newest code sent to your email.'
                            );
                        }

                        $verificationToken =
                            Str::random(64);

                        $verification
                            ->markAsVerified(
                                $verificationToken
                            );

                        return [
                            'message' =>
                            'Email verified successfully.',

                            'verification' =>
                            $verification->fresh(),
                        ];
                    }
                );
        } catch (HttpException $error) {
            return response()->json([
                'message' =>
                $error->getMessage(),
            ], $error->getStatusCode());
        } catch (Throwable $error) {
            report($error);

            return response()->json([
                'message' =>
                'Unable to verify the email address. Please try again.',
            ], 500);
        }

        /** @var EmailVerification $verification */
        $verification =
            $result['verification'];

        return response()->json([
            'message' =>
            $result['message'],

            'data' => [
                'verified' =>
                true,

                'verification_id' =>
                $verification->id,

                'email' =>
                $verification->email,

                'verification_token' =>
                $verification
                    ->verification_token,
            ],
        ]);
    }

    public function register(
        Request $request,
        string $token
    ): JsonResponse {
        $event =
            Event::query()
            ->whereHas(
                'registrationSettings',
                fn ($query) => $query->where('public_registration_token', $token)
            )
            ->with('registrationSettings')
            ->first();

        if (!$event) {
            return response()->json([
                'message' =>
                'Registration link not found.',
            ], 404);
        }

        if (!$event->public_registration) {
            return response()->json([
                'message' =>
                'Registration is currently closed for this event.',
            ], 422);
        }

        if ($event->require_approval) {
            return response()->json([
                'message' =>
                'This event requires organizer approval before QR tickets can be issued.',
            ], 422);
        }

        $validated =
            $request->validate([
                'attendees' => [
                    'required',
                    'array',
                    'min:1',
                    'max:20',
                ],

                'attendees.*.name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'attendees.*.email' => [
                    'required',
                    'email',
                    'max:255',
                ],

                'attendees.*.contact_no' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'attendees.*.email_verification_token' => [
                    'required',
                    'string',
                    'size:64',
                ],

                'attendees.*.event_ticket_type_id' => [
                    'required',
                    'integer',

                    Rule::exists(
                        'event_ticket_types',
                        'id'
                    )->where(
                        fn($query) =>
                        $query->where(
                            'event_id',
                            $event->id
                        )
                    ),
                ],
            ], [
                'attendees.required' =>
                'At least one attendee is required.',

                'attendees.min' =>
                'At least one attendee is required.',

                'attendees.max' =>
                'A maximum of 20 attendees can be registered at one time.',

                'attendees.*.name.required' =>
                'The attendee name is required.',

                'attendees.*.email.required' =>
                'The attendee email address is required.',

                'attendees.*.email.email' =>
                'Please provide a valid attendee email address.',

                'attendees.*.email_verification_token.required' =>
                'Please verify the attendee email address before registering.',

                'attendees.*.email_verification_token.size' =>
                'The attendee email verification is invalid. Please verify the email again.',

                'attendees.*.event_ticket_type_id.required' =>
                'Please select a ticket type for every attendee.',

                'attendees.*.event_ticket_type_id.exists' =>
                'One of the selected ticket types is invalid for this event.',
            ]);

        try {
            $tickets =
                DB::transaction(
                    function () use (
                        $validated,
                        $event,
                        $token
                    ) {
                        $lockedEvent =
                            Event::query()
                            ->whereKey(
                                $event->id
                            )
                            ->lockForUpdate()
                            ->first();

                        if (!$lockedEvent) {
                            abort(
                                404,
                                'Event not found.'
                            );
                        }

                        if (
                            !$lockedEvent
                                ->public_registration
                        ) {
                            abort(
                                422,
                                'Registration is currently closed for this event.'
                            );
                        }

                        if (
                            $lockedEvent
                            ->require_approval
                        ) {
                            abort(
                                422,
                                'This event requires organizer approval before QR tickets can be issued.'
                            );
                        }

                        $attendees =
                            collect(
                                $validated['attendees']
                            )
                            ->sortBy(
                                'event_ticket_type_id'
                            )
                            ->values();

                        $createdTickets = [];

                        foreach (
                            $attendees
                            as $attendee
                        ) {
                            $ticketType =
                                $lockedEvent
                                ->ticketTypes()
                                ->whereKey(
                                    $attendee['event_ticket_type_id']
                                )
                                ->lockForUpdate()
                                ->first();

                            if (!$ticketType) {
                                abort(
                                    422,
                                    'A selected ticket type does not belong to this event.'
                                );
                            }

                            $issued =
                                EventTicket::query()
                                ->where(
                                    'event_id',
                                    $lockedEvent->id
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
                                $issued >=
                                $ticketType->capacity
                            ) {
                                abort(
                                    422,
                                    $ticketType->name .
                                        ' is sold out.'
                                );
                            }

                            $fullName =
                                trim(
                                    $attendee['name']
                                );

                            $email =
                                strtolower(
                                    trim(
                                        $attendee['email']
                                    )
                                );

                            /*
                             * Server-side verification:
                             * - same email
                             * - same public event token
                             * - exact verification token
                             * - verified_at is present
                             * - used_at is still null
                             *
                             * The row is locked so the same verification token
                             * cannot be consumed by two concurrent requests.
                             */
                            $emailVerification =
                                EmailVerification::query()
                                ->where(
                                    'email',
                                    $email
                                )
                                ->where(
                                    'event_token',
                                    $token
                                )
                                ->where(
                                    'verification_token',
                                    $attendee['email_verification_token']
                                )
                                ->whereNotNull(
                                    'verified_at'
                                )
                                ->whereNull(
                                    'used_at'
                                )
                                ->lockForUpdate()
                                ->first();

                            if (!$emailVerification) {
                                abort(
                                    422,
                                    'The attendee email address has not been verified or the verification was already used.'
                                );
                            }

                            /*
                             * A verified email token may only be used to
                             * register within 30 minutes after verification.
                             */
                            if (
                                $emailVerification
                                ->verified_at
                                ->lt(
                                    now()->subMinutes(30)
                                )
                            ) {
                                abort(
                                    422,
                                    'The attendee email verification has expired. Please verify the email again.'
                                );
                            }

                            $contactNo =
                                !empty($attendee['contact_no'])
                                ? trim(
                                    $attendee['contact_no']
                                )
                                : null;

                            /*
                             * Reuse attendee by normalized email when possible.
                             */
                            $attendeeRecord =
                                Attendee::query()
                                ->firstOrCreate(
                                    [
                                        'email' =>
                                        $email,
                                    ],
                                    [
                                        'full_name' =>
                                        $fullName,

                                        'contact_no' =>
                                        $contactNo,
                                    ]
                                );

                            /*
                             * Keep attendee information current.
                             */
                            $attendeeRecord->fill([
                                'full_name' =>
                                $fullName,

                                'contact_no' =>
                                $contactNo,
                            ]);

                            if (
                                $attendeeRecord->isDirty()
                            ) {
                                $attendeeRecord->save();
                            }

                            /*
                             * Create normalized event registration.
                             */
                            $registration =
                                EventRegistration::create([
                                    'event_id' =>
                                    $lockedEvent->id,

                                    'attendee_id' =>
                                    $attendeeRecord
                                        ->attendee_id,

                                    'event_ticket_type_id' =>
                                    $ticketType->id,

                                    'source' =>
                                    'public',

                                    'attendee_category' =>
                                    (float) $ticketType->price > 0
                                        ? 'paid'
                                        : 'free',

                                    'status' =>
                                    'registered',

                                    'payment_status' =>
                                    (float) $ticketType->price > 0
                                        ? 'paid'
                                        : null,

                                    'registered_at' =>
                                    now(),

                                    'checked_in_at' =>
                                    null,

                                    'checked_in_by' =>
                                    null,
                                ]);

                            $qrToken =
                                $this
                                ->generateUniqueQRToken();

                            $qrValue =
                                'NASERY:TICKET:' .
                                $qrToken;

                            /*
                             * Save normalized QR ticket.
                             */
                            $qrTicket =
                                QrTicket::create([
                                    'registration_id' =>
                                    $registration
                                        ->registration_id,

                                    'qr_token' =>
                                    $qrToken,

                                    'qr_value' =>
                                    $qrValue,

                                    'generated_at' =>
                                    now(),

                                    'emailed_at' =>
                                    null,

                                    'downloaded_at' =>
                                    null,
                                ]);

                            /*
                             * Keep EventTicket for compatibility with the
                             * existing ticket list/check-in/PDF APIs.
                             */
                            $ticket =
                                EventTicket::create([
                                    'event_id' =>
                                    $lockedEvent->id,

                                    'event_ticket_type_id' =>
                                    $ticketType->id,

                                    'attendee_name' =>
                                    $fullName,

                                    'attendee_email' =>
                                    $email,

                                    'qr_token' =>
                                    $qrToken,

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

                            $ticket->load([
                                'ticketType',
                                'event',
                            ]);

                            $createdTickets[] =
                                $this->formatTicket(
                                    $ticket,
                                    $lockedEvent,
                                    $registration,
                                    $attendeeRecord,
                                    $qrTicket
                                );

                            /*
                             * IMPORTANT:
                             * Keep the email_verifications row for audit/history.
                             * Mark it as consumed instead of deleting it.
                             */
                            $emailVerification
                                ->markAsUsed();
                        }

                        return $createdTickets;
                    }
                );
        } catch (HttpException $error) {
            return response()->json([
                'message' =>
                $error->getMessage(),
            ], $error->getStatusCode());
        } catch (Throwable $error) {
            report($error);

            return response()->json([
                'message' =>
                'Unable to complete the registration. Please try again.',
            ], 500);
        }

        return response()->json([
            'message' =>
            count($tickets) > 1
                ? 'Attendees registered successfully. QR tickets have been generated.'
                : 'Attendee registered successfully. QR ticket has been generated.',

            'data' => [
                'event' => [
                    'id' =>
                    $event->id,

                    'name' =>
                    $event->name,
                ],

                'tickets' =>
                $tickets,
            ],
        ], 201);
    }

    public function ticket(
        string $qrToken
    ): JsonResponse {
        $ticket =
            $this->findTicket(
                $qrToken
            );

        if (!$ticket) {
            return response()->json([
                'message' =>
                'Ticket not found.',
            ], 404);
        }

        $normalized =
            $this
            ->findNormalizedTicketData(
                $ticket->qr_token
            );

        return response()->json([
            'data' =>
            $this->formatTicket(
                $ticket,
                $ticket->event,
                $normalized['registration'],
                $normalized['attendee'],
                $normalized['qr_ticket']
            ),
        ]);
    }

    public function downloadTicket(
        string $qrToken
    ) {
        $ticket =
            $this->findTicket(
                $qrToken
            );

        if (!$ticket) {
            return response()->json([
                'message' =>
                'Ticket not found.',
            ], 404);
        }

        try {
            $ticketId =
                $this->getTicketId(
                    $ticket
                );

            $qrValue =
                $this->getQRValue(
                    $ticket
                );

            $qrBase64 =
                $this->generateQRBase64(
                    $qrValue
                );

            $pdf =
                Pdf::loadView(
                    'tickets.pdf',
                    [
                        'ticket' =>
                        $ticket,

                        'event' =>
                        $ticket->event,

                        'ticketType' =>
                        $ticket->ticketType,

                        'ticketId' =>
                        $ticketId,

                        'qrValue' =>
                        $qrValue,

                        'qrBase64' =>
                        $qrBase64,
                    ]
                );

            $pdf->setPaper(
                'a4',
                'portrait'
            );

            /*
             * Record the PDF download in the normalized QR ticket.
             */
            QrTicket::query()
                ->where(
                    'qr_token',
                    $ticket->qr_token
                )
                ->update([
                    'downloaded_at' =>
                    now(),
                ]);

            return $pdf->download(
                $ticketId . '.pdf'
            );
        } catch (Throwable $error) {
            report($error);

            return response()->json([
                'message' =>
                'Unable to generate the ticket PDF.',

                'error' =>
                $error->getMessage(),

                'file' =>
                $error->getFile(),

                'line' =>
                $error->getLine(),
            ], 500);
        }
    }

    public function emailTicket(
        Request $request,
        string $qrToken
    ): JsonResponse {
        $ticket =
            $this->findTicket(
                $qrToken
            );

        if (!$ticket) {
            return response()->json([
                'message' =>
                'Ticket not found.',
            ], 404);
        }

        $validated =
            $request->validate([
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                ],
            ], [
                'email.email' =>
                'Please provide a valid email address.',
            ]);

        $email =
            !empty($validated['email'])
            ? strtolower(
                trim(
                    $validated['email']
                )
            )
            : strtolower(
                trim(
                    (string) $ticket
                        ->attendee_email
                )
            );

        if (!$email) {
            return response()->json([
                'message' =>
                'No email address was provided for this ticket.',
            ], 422);
        }

        try {
            $ticketId =
                $this->getTicketId(
                    $ticket
                );

            $qrValue =
                $this->getQRValue(
                    $ticket
                );

            $qrBase64 =
                $this->generateQRBase64(
                    $qrValue
                );

            $pdfContent =
                Pdf::loadView(
                    'tickets.pdf',
                    [
                        'ticket' =>
                        $ticket,

                        'event' =>
                        $ticket->event,

                        'ticketType' =>
                        $ticket->ticketType,

                        'ticketId' =>
                        $ticketId,

                        'qrValue' =>
                        $qrValue,

                        'qrBase64' =>
                        $qrBase64,
                    ]
                )
                ->setPaper(
                    'a4',
                    'portrait'
                )
                ->output();

            if (
                !$ticket->attendee_email &&
                !empty($validated['email'])
            ) {
                $ticket->update([
                    'attendee_email' =>
                    $email,
                ]);

                $ticket->refresh();

                $ticket->load([
                    'event',
                    'ticketType',
                ]);
            }

            Mail::to(
                $email
            )->send(
                new EventTicketMail(
                    $ticket,
                    $pdfContent
                )
            );

            /*
             * Record successful ticket email delivery.
             */
            QrTicket::query()
                ->where(
                    'qr_token',
                    $ticket->qr_token
                )
                ->update([
                    'emailed_at' =>
                    now(),
                ]);
        } catch (Throwable $error) {
            report($error);

            return response()->json([
                'message' =>
                'Unable to send the ticket email. Please try again.',
            ], 500);
        }

        return response()->json([
            'message' =>
            'Ticket sent successfully.',

            'data' => [
                'email' =>
                $email,

                'ticket_id' =>
                $ticketId,
            ],
        ]);
    }

    private function findTicket(
        string $qrToken
    ): ?EventTicket {
        return EventTicket::query()
            ->where(
                'qr_token',
                $qrToken
            )
            ->with([
                'event',
                'ticketType',
            ])
            ->first();
    }

    /**
     * Resolve the normalized registration records that belong
     * to an existing EventTicket QR token.
     */
    private function findNormalizedTicketData(
        string $qrToken
    ): array {
        $qrTicket =
            QrTicket::query()
            ->where(
                'qr_token',
                $qrToken
            )
            ->with([
                'registration.attendee',
            ])
            ->first();

        return [
            'qr_ticket' =>
            $qrTicket,

            'registration' =>
            $qrTicket?->registration,

            'attendee' =>
            $qrTicket
                ?->registration
                ?->attendee,
        ];
    }

    private function generateUniqueQRToken(): string
    {
        do {
            $token =
                Str::random(64);
        } while (
            EventTicket::query()
            ->where(
                'qr_token',
                $token
            )
            ->exists() ||
            QrTicket::query()
            ->where(
                'qr_token',
                $token
            )
            ->exists()
        );

        return $token;
    }

    private function getTicketId(
        EventTicket $ticket
    ): string {
        return 'TKT-' .
            str_pad(
                (string) $ticket->id,
                6,
                '0',
                STR_PAD_LEFT
            );
    }

    private function getQRValue(
        EventTicket $ticket
    ): string {
        return 'NASERY:TICKET:' .
            $ticket->qr_token;
    }

    private function generateQRBase64(
        string $qrValue
    ): string {
        $qr =
            QrCode::format('png')
            ->size(400)
            ->margin(1)
            ->generate(
                $qrValue
            );

        /*
         * Simple QR Code can return an HtmlString.
         * Convert it to the raw PNG byte string before
         * Base64 encoding it for DomPDF/email attachments.
         */
        $png =
            $qr instanceof \Illuminate\Support\HtmlString
            ? $qr->toHtml()
            : (string) $qr;

        return base64_encode(
            $png
        );
    }

    private function formatTicket(
        EventTicket $ticket,
        Event $event,
        ?EventRegistration $registration = null,
        ?Attendee $attendee = null,
        ?QrTicket $qrTicket = null
    ): array {
        if (
            !$ticket->relationLoaded(
                'ticketType'
            )
        ) {
            $ticket->load(
                'ticketType'
            );
        }

        return [
            'id' =>
            $ticket->id,

            'registration_id' =>
            $registration
                ?->registration_id,

            'attendee_id' =>
            $attendee
                ?->attendee_id,

            'qr_ticket_id' =>
            $qrTicket
                ?->qr_ticket_id,

            'ticket_id' =>
            $this->getTicketId(
                $ticket
            ),

            'name' =>
            $ticket->attendee_name,

            'email' =>
            $ticket->attendee_email,

            'ticket_type' =>
            $ticket->ticketType,

            'qr_token' =>
            $ticket->qr_token,

            'qr_value' =>
            $this->getQRValue(
                $ticket
            ),

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

            'event' => [
                'id' =>
                $event->id,

                'name' =>
                $event->name,

                'event_type' =>
                $event->event_type,

                'event_date' =>
                $event->event_date,

                'location' =>
                $event->location,

                'start_time' =>
                $event->start_time,

                'end_time' =>
                $event->end_time,
            ],

            'download_url' =>
            url(
                '/api/tickets/' .
                    rawurlencode(
                        $ticket->qr_token
                    ) .
                    '/download'
            ),
        ];
    }
}

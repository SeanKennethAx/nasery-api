<?php

use App\Http\Controllers\PublicEventMarketplaceController;
use App\Http\Controllers\PublicEventPaymentController;
use App\Http\Controllers\PublicEventRegistrationController;
use App\Http\Controllers\PublicOrganizerController;
use Illuminate\Support\Facades\Route;

Route::get(
    '/marketplace/events',
    [PublicEventMarketplaceController::class, 'index']
);

Route::get(
    '/organizers',
    [PublicOrganizerController::class, 'index']
);

Route::get(
    '/organizers/{organizer}',
    [PublicOrganizerController::class, 'show']
)->where(
    'organizer',
    '[0-9]+|.+-[0-9]+'
);

Route::get(
    '/events/register/{token}',
    [
        PublicEventRegistrationController::class,
        'show',
    ]
);

Route::post(
    '/events/register/{token}/email-verification/send',
    [
        PublicEventRegistrationController::class,
        'sendEmailVerificationCode',
    ]
);
Route::get(
    '/event-payments/{reference}',
    [PublicEventPaymentController::class, 'show']
);
Route::post(
    '/paymongo/webhook',
    [PublicEventPaymentController::class, 'webhook']
);

Route::post(
    '/events/register/{token}/email-verification/verify',
    [
        PublicEventRegistrationController::class,
        'verifyEmailVerificationCode',
    ]
);
Route::post(
    '/events/register/{token}',
    [
        PublicEventRegistrationController::class,
        'register',
    ]
);
Route::get(
    '/tickets/{qrToken}',
    [
        PublicEventRegistrationController::class,
        'ticket',
    ]
);
Route::get(
    '/tickets/{qrToken}/download',
    [
        PublicEventRegistrationController::class,
        'downloadTicket',
    ]
);
Route::post(
    '/tickets/{qrToken}/email',
    [
        PublicEventRegistrationController::class,
        'emailTicket',
    ]
);

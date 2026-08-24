<?php

use App\Http\Controllers\EventActivityController;
use App\Http\Controllers\EventAttendeeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventPreparationController;
use App\Http\Controllers\EventQRCheckInController;
use App\Http\Controllers\EventWalkInController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\QuotationController;
use Illuminate\Support\Facades\Route;

Route::get(
    '/organizer/profile',
    [OrganizerController::class, 'profile']
);

Route::put(
    '/organizer/profile',
    [OrganizerController::class, 'updateProfile']
);

Route::get(
    '/organizer/inquiries/matching',
    [InquiryController::class, 'matching']
);

Route::get(
    '/organizer/quotations',
    [QuotationController::class, 'organizerQuotations']
);

Route::post(
    '/organizer/quotations',
    [QuotationController::class, 'store']
);

Route::get(
    '/organizer/events',
    [EventController::class, 'index']
);

Route::post(
    '/organizer/events',
    [EventController::class, 'store']
);

Route::get(
    '/organizer/events/{event}',
    [EventController::class, 'show']
);

Route::put(
    '/organizer/events/{event}',
    [EventController::class, 'update']
);

Route::get(
    '/organizer/events/{event}/preparation-items',
    [EventPreparationController::class, 'index']
);

Route::post(
    '/organizer/events/{event}/preparation-items',
    [EventPreparationController::class, 'store']
);

Route::put(
    '/organizer/events/{event}/preparation-items/{item}',
    [EventPreparationController::class, 'update']
);

Route::delete(
    '/organizer/events/{event}/preparation-items/{item}',
    [EventPreparationController::class, 'destroy']
);

Route::get(
    '/organizer/activities',
    [EventActivityController::class, 'index']
);

Route::get(
    '/organizer/events/{event}/activities',
    [EventActivityController::class, 'eventActivities']
);

Route::post(
    '/organizer/events/{event}/activities',
    [EventActivityController::class, 'store']
);

Route::put(
    '/organizer/events/{event}/activities/{activity}',
    [EventActivityController::class, 'update']
);

Route::delete(
    '/organizer/events/{event}/activities/{activity}',
    [EventActivityController::class, 'destroy']
);

Route::get(
    '/organizer/quotations/{quotation}/event-data',
    [QuotationController::class, 'eventData']
);

Route::post(
    '/organizer/quotations/{quotation}/start-planning',
    [QuotationController::class, 'startPlanning']
);

Route::get(
    '/organizer/events/{event}/check-in-summary',
    [EventQRCheckInController::class, 'summary']
);

Route::post(
    '/organizer/events/{event}/check-in',
    [EventQRCheckInController::class, 'scan']
);

Route::post(
    '/organizer/events/{event}/manual-check-in',
    [EventQRCheckInController::class, 'manual']
);

Route::get(
    '/organizer/events/{event}/walk-ins',
    [EventWalkInController::class, 'index']
);

Route::get(
    '/organizer/events/{event}/walk-in-summary',
    [EventWalkInController::class, 'summary']
);

Route::post(
    '/organizer/events/{event}/walk-ins',
    [EventWalkInController::class, 'store']
);

Route::post(
    '/organizer/events/{event}/walk-ins/{walkIn}/process-payment',
    [EventWalkInController::class, 'processPayment']
);

Route::delete(
    '/organizer/events/{event}/walk-ins/{walkIn}',
    [EventWalkInController::class, 'destroy']
);

Route::get(
    '/organizer/events/{event}/attendees',
    [EventAttendeeController::class, 'index']
);

Route::post(
    '/organizer/events/{event}/attendees',
    [EventAttendeeController::class, 'store']
);

Route::get(
    '/organizer/events/{event}/attendee-summary',
    [EventAttendeeController::class, 'summary']
);

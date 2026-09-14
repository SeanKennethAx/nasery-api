<?php

use App\Http\Controllers\ClientEventTicketController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\OrganizerReviewController;
use App\Http\Controllers\QuotationController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/inquiries',
    [InquiryController::class, 'store']
);

Route::get(
    '/inquiries/client',
    [InquiryController::class, 'clientInquiries']
);

Route::get(
    '/inquiries/{inquiry}/quotations',
    [QuotationController::class, 'inquiryQuotations']
);

Route::post(
    '/quotations/{quotation}/accept',
    [QuotationController::class, 'accept']
);

Route::get(
    '/organizers/nearby',
    [OrganizerController::class, 'nearby']
);

Route::get(
    '/organizers/{organizer}/reviews',
    [OrganizerReviewController::class, 'index']
);

Route::post(
    '/organizers/{organizer}/reviews',
    [OrganizerReviewController::class, 'store']
);

Route::get(
    '/notifications',
    [NotificationController::class, 'index']
);

Route::post(
    '/notifications/{notificationId}/read',
    [NotificationController::class, 'markAsRead']
);

Route::post(
    '/notifications/{notificationId}/unread',
    [NotificationController::class, 'markAsUnread']
);

Route::delete(
    '/notifications/{notificationId}',
    [NotificationController::class, 'destroy']
);

Route::post(
    '/notifications/read-all',
    [NotificationController::class, 'markAllAsRead']
);

Route::get(
    '/client/inquiries/{inquiry}/event',
    [QuotationController::class, 'clientEventDetails']
);

Route::get(
    '/client/events/{event}/tickets',
    [ClientEventTicketController::class, 'index']
);

Route::post(
    '/client/events/{event}/tickets',
    [ClientEventTicketController::class, 'store']
);

Route::get(
    '/client/events/{event}/tickets/{ticket}',
    [ClientEventTicketController::class, 'show']
);

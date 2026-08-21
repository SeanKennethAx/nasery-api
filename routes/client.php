<?php

use App\Http\Controllers\InquiryController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\NotificationController;
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
    '/notifications',
    [NotificationController::class, 'index']
);

Route::post(
    '/notifications/{notificationId}/read',
    [NotificationController::class, 'markAsRead']
);

Route::post(
    '/notifications/read-all',
    [NotificationController::class, 'markAllAsRead']
);

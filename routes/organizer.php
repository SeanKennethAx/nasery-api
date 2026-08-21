<?php

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

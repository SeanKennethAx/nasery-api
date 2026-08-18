<?php

use App\Http\Controllers\InquiryController;
use App\Http\Controllers\OrganizerController;
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

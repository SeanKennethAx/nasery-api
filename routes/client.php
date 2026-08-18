<?php

use App\Http\Controllers\InquiryController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/inquiries',
    [InquiryController::class, 'store']
);

Route::get(
    '/inquiries/client',
    [InquiryController::class, 'clientInquiries']
);

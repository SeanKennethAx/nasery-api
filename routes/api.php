<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

require 'unauthenticated.php';

Route::middleware('auth:sanctum')->group(function () {

    Route::get(
        '/auth/me',
        [UserController::class, 'me']
    );

    Route::middleware('role:client')->group(function () {
        require 'client.php';
    });

    Route::middleware('role:organizer')->group(function () {
        require 'organizer.php';
    });
});

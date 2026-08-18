<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

require 'unauthenticated.php';

Route::middleware('auth:sanctum')->group(function () {

    Route::get(
        '/auth/me',
        [UserController::class, 'me']
    );

    require 'client.php';
    require 'organizer.php';
});

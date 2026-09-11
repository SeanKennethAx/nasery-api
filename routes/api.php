<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post(
        '/register',
        [UserController::class, 'register']
    );

    Route::post(
        '/login',
        [UserController::class, 'login']
    );
});

require 'public.php';

Route::middleware('auth:sanctum')->group(function () {

    Route::get(
        '/auth/me',
        [UserController::class, 'me']
    );

    Route::put(
        '/auth/profile',
        [UserController::class, 'updateProfile']
    );

    Route::post(
        '/auth/profile/media',
        [UserController::class, 'updateProfileMedia']
    );

    Route::middleware('role:client')->group(function () {
        require 'client.php';
    });

    Route::middleware('role:organizer')->group(function () {
        require 'organizer.php';
    });

    Route::middleware('role:team_member')->group(function () {
        require 'team.php';
    });
});

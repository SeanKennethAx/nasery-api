<?php

use App\Http\Controllers\AccountEmailVerificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/email-verification/send', [AccountEmailVerificationController::class, 'send'])->middleware('throttle:10,1');
    Route::post('/email-verification/verify', [AccountEmailVerificationController::class, 'verify'])->middleware('throttle:30,1');
    Route::post('/forgot-password/send', [PasswordResetController::class, 'send'])->middleware('throttle:5,1');
    Route::post('/forgot-password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');
    Route::get('/social/{provider}/redirect', [SocialAuthController::class, 'redirect']);
    Route::match(['get', 'post'], '/social/{provider}/callback', [SocialAuthController::class, 'callback']);
    Route::post('/social/exchange', [SocialAuthController::class, 'exchange'])->middleware('throttle:20,1');

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

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/{notificationId}/unread', [NotificationController::class, 'markAsUnread']);
    Route::delete('/notifications/{notificationId}', [NotificationController::class, 'destroy']);

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

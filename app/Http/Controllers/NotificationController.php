<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'data' => $user->notifications()->latest()->limit(50)->get(),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(
        Request $request,
        string $notificationId
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' =>
                'Unauthenticated.',
            ], 401);
        }

        $notification =
            $user
            ->notifications()
            ->where(
                'id',
                $notificationId
            )
            ->first();

        if (!$notification) {
            return response()->json([
                'message' =>
                'Notification not found.',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' =>
            'Notification marked as read.',
        ]);
    }

    public function markAllAsRead(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' =>
                'Unauthenticated.',
            ], 401);
        }

        $user
            ->unreadNotifications
            ->markAsRead();

        return response()->json([
            'message' =>
            'All notifications marked as read.',
        ]);
    }
}

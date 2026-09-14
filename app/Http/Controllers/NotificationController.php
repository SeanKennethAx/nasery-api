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

        $validated = $request->validate([
            'filter' => ['nullable', 'in:all,unread'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $query = $user->notifications()->latest();

        if (($validated['filter'] ?? 'all') === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate($validated['per_page'] ?? 10);

        return response()->json([
            'data' => $notifications->items(),
            'unread_count' => $user->unreadNotifications()->count(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function markAsUnread(
        Request $request,
        string $notificationId
    ): JsonResponse {
        $notification = $request->user()
            ?->notifications()
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found.',
            ], 404);
        }

        $notification->markAsUnread();

        return response()->json([
            'message' => 'Notification marked as unread.',
        ]);
    }

    public function destroy(
        Request $request,
        string $notificationId
    ): JsonResponse {
        $notification = $request->user()
            ?->notifications()
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found.',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification removed.',
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

<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $notifications = $this->notificationService->getNotifications(
            $user,
            $request->boolean('unread_only', false),
            $request->notification_type,
            $request->limit ?? 50
        );

        return response()->json([
            'status' => 'success',
            'data' => $notifications,
        ]);
    }

    public function unreadCount(Request $request)
    {
        $user = Auth::user();

        $count = $this->notificationService->getUnreadCount($user);

        return response()->json([
            'status' => 'success',
            'data' => ['count' => $count],
        ]);
    }

    public function markAsRead(Notification $notification)
    {
        $user = Auth::user();

        if ($notification->user_id !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $this->notificationService->markAsRead($notification);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read',
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();

        $count = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'status' => 'success',
            'message' => "{$count} notifications marked as read",
        ]);
    }

    public function archive(Notification $notification)
    {
        $user = Auth::user();

        if ($notification->user_id !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $this->notificationService->archive($notification);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification archived successfully',
        ]);
    }

    public function preferences(Request $request)
    {
        $user = Auth::user();

        $preferences = $this->notificationService->getPreferences($user);

        return response()->json([
            'status' => 'success',
            'data' => $preferences,
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'notification_type' => 'required|string',
            'enabled' => 'required|boolean',
            'in_app' => 'boolean',
            'email' => 'boolean',
            'sms' => 'boolean',
            'push' => 'boolean',
            'frequency' => 'in:instant,hourly,daily,weekly',
            'quiet_hours' => 'nullable|array',
        ]);

        $preference = $this->notificationService->updatePreference(
            $user,
            $request->notification_type,
            $request->enabled,
            $request->in_app ?? true,
            $request->email ?? true,
            $request->sms ?? false,
            $request->push ?? false,
            $request->frequency ?? 'instant',
            $request->quiet_hours
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Preferences updated successfully',
            'data' => $preference,
        ]);
    }

    public function stats(Request $request)
    {
        $user = Auth::user();

        $stats = $this->notificationService->getNotificationStats($user);

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Send notification
     */
    public function notify(
        int $userId,
        string $notificationType,
        string $title,
        string $message,
        ?int $vendorId = null,
        ?Model $notifiable = null,
        ?array $actionData = null,
        ?string $actionUrl = null,
        string $priority = 'medium',
        ?string $icon = null,
        ?string $color = null,
        ?string $groupKey = null
    ): Notification {
        // Get user preferences
        $preference = NotificationPreference::getOrCreateDefault($userId, $notificationType);

        // Check if notification is enabled
        if (!$preference->isEnabled()) {
            // Still create notification but don't send via channels
            return $this->createNotification(
                $userId,
                $notificationType,
                $title,
                $message,
                $vendorId,
                $notifiable,
                $actionData,
                $actionUrl,
                $priority,
                $icon,
                $color,
                $groupKey,
                false, false, false, false
            );
        }

        // Determine channels based on preferences
        $sendInApp = $preference->shouldSend('in_app');
        $sendEmail = $preference->shouldSend('email');
        $sendSms = $preference->shouldSend('sms');
        $sendPush = $preference->shouldSend('push');

        // Create notification
        $notification = $this->createNotification(
            $userId,
            $notificationType,
            $title,
            $message,
            $vendorId,
            $notifiable,
            $actionData,
            $actionUrl,
            $priority,
            $icon,
            $color,
            $groupKey,
            $sendInApp,
            $sendEmail,
            $sendSms,
            $sendPush
        );

        // Send via channels (implement actual sending logic as needed)
        if ($sendEmail) {
            $this->sendEmail($notification);
        }

        if ($sendSms) {
            $this->sendSms($notification);
        }

        if ($sendPush) {
            $this->sendPush($notification);
        }

        return $notification;
    }

    /**
     * Create notification record
     */
    protected function createNotification(
        int $userId,
        string $notificationType,
        string $title,
        string $message,
        ?int $vendorId,
        ?Model $notifiable,
        ?array $actionData,
        ?string $actionUrl,
        string $priority,
        ?string $icon,
        ?string $color,
        ?string $groupKey,
        bool $sendInApp,
        bool $sendEmail,
        bool $sendSms,
        bool $sendPush
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'vendor_id' => $vendorId,
            'notifiable_id' => $notifiable?->id,
            'notifiable_type' => $notifiable ? get_class($notifiable) : null,
            'notification_type' => $notificationType,
            'title' => $title,
            'message' => $message,
            'action_data' => $actionData,
            'action_url' => $actionUrl,
            'priority' => $priority,
            'icon' => $icon,
            'color' => $color,
            'group_key' => $groupKey,
            'sent_in_app' => $sendInApp,
            'sent_email' => $sendEmail,
            'sent_sms' => $sendSms,
            'sent_push' => $sendPush,
        ]);
    }

    /**
     * Send email notification
     */
    protected function sendEmail(Notification $notification): void
    {
        // Implement email sending logic
        // Example: Mail::to($notification->user)->send(new NotificationMail($notification));
    }

    /**
     * Send SMS notification
     */
    protected function sendSms(Notification $notification): void
    {
        // Implement SMS sending logic
        // Example: SMS::to($notification->user->phone)->send($notification->message);
    }

    /**
     * Send push notification
     */
    protected function sendPush(Notification $notification): void
    {
        // Implement push notification logic
        // Example: PushNotification::send($notification->user, $notification);
    }

    /**
     * Bulk notify users
     */
    public function bulkNotify(
        array $userIds,
        string $notificationType,
        string $title,
        string $message,
        ?int $vendorId = null,
        ?Model $notifiable = null,
        ?array $actionData = null,
        ?string $actionUrl = null,
        string $priority = 'medium'
    ): void {
        foreach ($userIds as $userId) {
            $this->notify(
                $userId,
                $notificationType,
                $title,
                $message,
                $vendorId,
                $notifiable,
                $actionData,
                $actionUrl,
                $priority
            );
        }
    }

    /**
     * Get notifications for user
     */
    public function getNotifications(
        User $user,
        ?bool $unreadOnly = false,
        ?string $notificationType = null,
        int $limit = 50
    ) {
        $query = Notification::forUser($user->id)
            ->notArchived()
            ->with('notifiable')
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($unreadOnly) {
            $query->unread();
        }

        if ($notificationType) {
            $query->byType($notificationType);
        }

        return $query->get();
    }

    /**
     * Get unread count
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::forUser($user->id)
            ->unread()
            ->notArchived()
            ->count();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::forUser($user->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Mark as unread
     */
    public function markAsUnread(Notification $notification): void
    {
        $notification->markAsUnread();
    }

    /**
     * Archive notification
     */
    public function archive(Notification $notification): void
    {
        $notification->archive();
    }

    /**
     * Bulk archive
     */
    public function bulkArchive(array $notificationIds): int
    {
        return Notification::whereIn('id', $notificationIds)
            ->update([
                'is_archived' => true,
                'archived_at' => now(),
            ]);
    }

    /**
     * Delete notification
     */
    public function delete(Notification $notification): void
    {
        $notification->delete();
    }

    /**
     * Get notification preferences
     */
    public function getPreferences(User $user)
    {
        return NotificationPreference::forUser($user->id)->get();
    }

    /**
     * Update preference
     */
    public function updatePreference(
        User $user,
        string $notificationType,
        bool $enabled,
        bool $inApp = true,
        bool $email = true,
        bool $sms = false,
        bool $push = false,
        string $frequency = 'instant',
        ?array $quietHours = null
    ): NotificationPreference {
        $preference = NotificationPreference::getOrCreateDefault($user->id, $notificationType);

        $preference->update([
            'enabled' => $enabled,
            'in_app' => $inApp,
            'email' => $email,
            'sms' => $sms,
            'push' => $push,
            'frequency' => $frequency,
            'quiet_hours' => $quietHours,
        ]);

        return $preference->fresh();
    }

    /**
     * Bulk update preferences
     */
    public function bulkUpdatePreferences(User $user, array $preferences): void
    {
        foreach ($preferences as $type => $settings) {
            $this->updatePreference(
                $user,
                $type,
                $settings['enabled'] ?? true,
                $settings['in_app'] ?? true,
                $settings['email'] ?? true,
                $settings['sms'] ?? false,
                $settings['push'] ?? false,
                $settings['frequency'] ?? 'instant',
                $settings['quiet_hours'] ?? null
            );
        }
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $notifications = Notification::forUser($user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'total_notifications' => $notifications->count(),
            'unread' => $notifications->where('is_read', false)->count(),
            'read' => $notifications->where('is_read', true)->count(),
            'archived' => $notifications->where('is_archived', true)->count(),
            'by_type' => $notifications->groupBy('notification_type')->map->count(),
            'by_priority' => $notifications->groupBy('priority')->map->count(),
            'high_priority' => $notifications->whereIn('priority', ['high', 'urgent'])->count(),
            'sent_via_email' => $notifications->where('sent_email', true)->count(),
            'sent_via_sms' => $notifications->where('sent_sms', true)->count(),
            'sent_via_push' => $notifications->where('sent_push', true)->count(),
        ];
    }

    /**
     * Clean old notifications
     */
    public function cleanOldNotifications(int $daysToKeep = 90): int
    {
        return Notification::where('created_at', '<', now()->subDays($daysToKeep))
            ->where('is_read', true)
            ->delete();
    }

    /**
     * System announcement
     */
    public function systemAnnouncement(
        string $title,
        string $message,
        ?string $actionUrl = null,
        string $priority = 'medium'
    ): void {
        // Notify all users
        $users = User::all();

        foreach ($users as $user) {
            $this->notify(
                $user->id,
                'system_announcement',
                $title,
                $message,
                null,
                null,
                null,
                $actionUrl,
                $priority
            );
        }
    }

    /**
     * Vendor announcement
     */
    public function vendorAnnouncement(
        int $vendorId,
        string $title,
        string $message,
        ?string $actionUrl = null,
        string $priority = 'medium'
    ): void {
        // Notify all users of this vendor
        $users = User::where('role', 'vendor')
            ->whereHas('vendorProfile', function($q) use ($vendorId) {
                $q->where('user_id', $vendorId);
            })
            ->get();

        foreach ($users as $user) {
            $this->notify(
                $user->id,
                'vendor_announcement',
                $title,
                $message,
                $vendorId,
                null,
                null,
                $actionUrl,
                $priority
            );
        }
    }
}

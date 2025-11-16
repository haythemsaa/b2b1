<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vendor_id',
        'notifiable_id',
        'notifiable_type',
        'notification_type',
        'title',
        'message',
        'action_data',
        'action_url',
        'priority',
        'icon',
        'color',
        'sent_in_app',
        'sent_email',
        'sent_sms',
        'sent_push',
        'is_read',
        'read_at',
        'is_archived',
        'archived_at',
        'group_key',
        'parent_notification_id',
    ];

    protected $casts = [
        'action_data' => 'array',
        'sent_in_app' => 'boolean',
        'sent_email' => 'boolean',
        'sent_sms' => 'boolean',
        'sent_push' => 'boolean',
        'is_read' => 'boolean',
        'is_archived' => 'boolean',
        'read_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get the notifiable item
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get parent notification
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Notification::class, 'parent_notification_id');
    }

    /**
     * Mark as read
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Mark as unread
     */
    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Archive notification
     */
    public function archive(): void
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);
    }

    /**
     * Unarchive notification
     */
    public function unarchive(): void
    {
        $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);
    }

    /**
     * Check if high priority
     */
    public function isHighPriority(): bool
    {
        return in_array($this->priority, ['high', 'urgent']);
    }

    /**
     * Check if urgent
     */
    public function isUrgent(): bool
    {
        return $this->priority === 'urgent';
    }

    /**
     * Get priority color
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'red',
            'high' => 'orange',
            'medium' => 'blue',
            'low' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Scope: Unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: Read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope: For user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: For vendor
     */
    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope: By type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope: By priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: High priority
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Scope: Not archived
     */
    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope: By group
     */
    public function scopeByGroup($query, string $groupKey)
    {
        return $query->where('group_key', $groupKey);
    }

    /**
     * Scope: Recent (last 7 days)
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Create notification helper
     */
    public static function notify(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?int $vendorId = null,
        ?Model $notifiable = null,
        ?array $actionData = null,
        ?string $actionUrl = null,
        string $priority = 'medium'
    ): self {
        return static::create([
            'user_id' => $userId,
            'vendor_id' => $vendorId,
            'notifiable_id' => $notifiable?->id,
            'notifiable_type' => $notifiable ? get_class($notifiable) : null,
            'notification_type' => $type,
            'title' => $title,
            'message' => $message,
            'action_data' => $actionData,
            'action_url' => $actionUrl,
            'priority' => $priority,
        ]);
    }
}

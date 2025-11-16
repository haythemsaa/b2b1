<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_type',
        'enabled',
        'in_app',
        'email',
        'sms',
        'push',
        'frequency',
        'quiet_hours',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'in_app' => 'boolean',
        'email' => 'boolean',
        'sms' => 'boolean',
        'push' => 'boolean',
        'quiet_hours' => 'array',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if notification type is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if currently in quiet hours
     */
    public function isQuietTime(): bool
    {
        if (!$this->quiet_hours) {
            return false;
        }

        $currentHour = now()->hour;
        $start = $this->quiet_hours['start'] ?? null;
        $end = $this->quiet_hours['end'] ?? null;

        if ($start === null || $end === null) {
            return false;
        }

        if ($start < $end) {
            return $currentHour >= $start && $currentHour < $end;
        } else {
            // Quiet hours span midnight
            return $currentHour >= $start || $currentHour < $end;
        }
    }

    /**
     * Check if should send via channel
     */
    public function shouldSend(string $channel): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if ($this->isQuietTime() && $channel !== 'in_app') {
            return false;
        }

        return match($channel) {
            'in_app' => $this->in_app,
            'email' => $this->email,
            'sms' => $this->sms,
            'push' => $this->push,
            default => false,
        };
    }

    /**
     * Get or create default preferences
     */
    public static function getOrCreateDefault(int $userId, string $notificationType): self
    {
        return static::firstOrCreate(
            [
                'user_id' => $userId,
                'notification_type' => $notificationType,
            ],
            [
                'enabled' => true,
                'in_app' => true,
                'email' => true,
                'sms' => false,
                'push' => false,
                'frequency' => 'instant',
            ]
        );
    }

    /**
     * Create default preferences for user
     */
    public static function createDefaultsForUser(int $userId): void
    {
        $types = [
            'order_status_change',
            'approval_required',
            'approval_approved',
            'approval_rejected',
            'budget_alert',
            'budget_exceeded',
            'negotiation_received',
            'negotiation_accepted',
            'negotiation_rejected',
            'rfq_quote_received',
            'invoice_due_soon',
            'invoice_overdue',
            'document_shared',
            'document_expiring',
            'chat_message',
            'system_announcement',
            'account_suspended',
            'low_stock_alert',
        ];

        foreach ($types as $type) {
            static::getOrCreateDefault($userId, $type);
        }
    }

    /**
     * Scope: For user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Enabled only
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope: By type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }
}

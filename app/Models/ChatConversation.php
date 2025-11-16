<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    protected $fillable = [
        'vendor_id',
        'last_message_at',
        'unread_vendor_count',
        'unread_admin_count',
        'is_active',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'unread_vendor_count' => 'integer',
        'unread_admin_count' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relations
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class, 'conversation_id')->latestOfMany();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithUnreadMessages($query, bool $forAdmin = false)
    {
        $column = $forAdmin ? 'unread_admin_count' : 'unread_vendor_count';
        return $query->where($column, '>', 0);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('last_message_at', 'desc');
    }

    // Helper Methods
    public function markAsReadForVendor(): void
    {
        $this->update(['unread_vendor_count' => 0]);

        $this->messages()
            ->where('is_read', false)
            ->whereHas('sender', fn($q) => $q->where('role', 'admin'))
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function markAsReadForAdmin(): void
    {
        $this->update(['unread_admin_count' => 0]);

        $this->messages()
            ->where('is_read', false)
            ->whereHas('sender', fn($q) => $q->where('role', 'vendor'))
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function hasUnreadMessages(bool $forAdmin = false): bool
    {
        return $forAdmin
            ? $this->unread_admin_count > 0
            : $this->unread_vendor_count > 0;
    }

    public function incrementUnreadCount(bool $forAdmin = false): void
    {
        $column = $forAdmin ? 'unread_admin_count' : 'unread_vendor_count';
        $this->increment($column);
    }
}

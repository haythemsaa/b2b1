<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message',
        'attachments',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Relations
    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeFromAdmin($query)
    {
        return $query->whereHas('sender', fn($q) => $q->where('role', 'admin'));
    }

    public function scopeFromVendor($query)
    {
        return $query->whereHas('sender', fn($q) => $q->where('role', 'vendor'));
    }

    // Helper Methods
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    public function isSentByAdmin(): bool
    {
        return $this->sender->isAdmin();
    }

    public function isSentByVendor(): bool
    {
        return $this->sender->isVendor();
    }
}

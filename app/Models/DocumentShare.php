<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'shared_by',
        'shared_with',
        'permission',
        'expires_at',
        'share_message',
        'is_accessed',
        'first_accessed_at',
        'last_accessed_at',
        'access_count',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_accessed' => 'boolean',
        'first_accessed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Get the document
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Get the sharer
     */
    public function sharer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    /**
     * Get the recipient
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with');
    }

    /**
     * Check if share is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if share is active
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Check if user can download
     */
    public function canDownload(): bool
    {
        return in_array($this->permission, ['download', 'edit']) && $this->isActive();
    }

    /**
     * Check if user can edit
     */
    public function canEdit(): bool
    {
        return $this->permission === 'edit' && $this->isActive();
    }

    /**
     * Record access
     */
    public function recordAccess(): void
    {
        $this->increment('access_count');

        if (!$this->is_accessed) {
            $this->update([
                'is_accessed' => true,
                'first_accessed_at' => now(),
                'last_accessed_at' => now(),
            ]);
        } else {
            $this->update(['last_accessed_at' => now()]);
        }
    }

    /**
     * Scope: Active shares
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: For user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('shared_with', $userId);
    }

    /**
     * Scope: Expired shares
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<', now());
    }

    /**
     * Scope: Unaccessed shares
     */
    public function scopeUnaccessed($query)
    {
        return $query->where('is_accessed', false);
    }
}

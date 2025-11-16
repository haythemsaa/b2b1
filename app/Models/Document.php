<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'uploaded_by',
        'documentable_id',
        'documentable_type',
        'name',
        'description',
        'document_type',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'file_hash',
        'document_number',
        'document_date',
        'expiry_date',
        'metadata',
        'visibility',
        'requires_approval',
        'approval_status',
        'approved_by',
        'approved_at',
        'is_archived',
        'is_confidential',
        'download_count',
        'last_downloaded_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'document_date' => 'date',
        'expiry_date' => 'date',
        'approved_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'is_archived' => 'boolean',
        'is_confidential' => 'boolean',
        'requires_approval' => 'boolean',
    ];

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get the uploader
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the approver
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the documentable item (order, RFQ, invoice, etc.)
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get document shares
     */
    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class, 'document_id');
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file URL
     */
    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Check if document is expiring soon (within 30 days)
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->isFuture() &&
               $this->expiry_date->diffInDays(now()) <= 30;
    }

    /**
     * Check if document is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if user can access document
     */
    public function canAccess(User $user): bool
    {
        // Vendor can always access their own documents
        if ($this->vendor_id === $user->id) {
            return true;
        }

        // Check if document is shared with user
        if ($this->visibility === 'shared') {
            return $this->shares()
                ->where('shared_with', $user->id)
                ->where(function($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })
                ->exists();
        }

        // Admin can access all documents
        return $user->role === 'admin';
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
    }

    /**
     * Approve document
     */
    public function approve(int $approverId): void
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject document
     */
    public function reject(int $approverId): void
    {
        $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Archive document
     */
    public function archive(): void
    {
        $this->update(['is_archived' => true]);
    }

    /**
     * Unarchive document
     */
    public function unarchive(): void
    {
        $this->update(['is_archived' => false]);
    }

    /**
     * Delete file from storage
     */
    public function deleteFile(): void
    {
        if (Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }
    }

    /**
     * Scope: For vendor
     */
    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope: By document type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope: Not archived
     */
    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope: Expiring soon
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
                     ->where('expiry_date', '>', now())
                     ->where('expiry_date', '<=', now()->addDays($days));
    }

    /**
     * Scope: Expired
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
                     ->where('expiry_date', '<', now());
    }

    /**
     * Scope: Pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('requires_approval', true)
                     ->where('approval_status', 'pending');
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            // Delete file from storage when model is deleted
            $document->deleteFile();
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceNegotiation extends Model
{
    use HasFactory;

    protected $fillable = [
        'rfq_id',
        'vendor_id',
        'initiated_by',
        'negotiation_type',
        'original_price',
        'proposed_price',
        'final_price',
        'terms',
        'status',
        'round_number',
        'max_rounds',
        'expires_at',
        'decided_by',
        'decision_reason',
        'decided_at',
    ];

    protected $casts = [
        'original_price' => 'decimal:3',
        'proposed_price' => 'decimal:3',
        'final_price' => 'decimal:3',
        'terms' => 'array',
        'expires_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    /**
     * Get the RFQ
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class, 'rfq_id');
    }

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get the initiator
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Get the decider
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * Get negotiation messages
     */
    public function messages(): HasMany
    {
        return $this->hasMany(NegotiationMessage::class, 'negotiation_id');
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute(): float
    {
        if ($this->original_price == 0) {
            return 0;
        }
        return (($this->original_price - $this->proposed_price) / $this->original_price) * 100;
    }

    /**
     * Get discount amount
     */
    public function getDiscountAmountAttribute(): float
    {
        return $this->original_price - $this->proposed_price;
    }

    /**
     * Check if negotiation is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Check if negotiation is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if can counter
     */
    public function canCounter(): bool
    {
        return $this->isActive() && $this->round_number < $this->max_rounds;
    }

    /**
     * Accept negotiation
     */
    public function accept(int $userId, ?string $reason = null): void
    {
        $this->update([
            'status' => 'accepted',
            'final_price' => $this->proposed_price,
            'decided_by' => $userId,
            'decision_reason' => $reason,
            'decided_at' => now(),
        ]);
    }

    /**
     * Reject negotiation
     */
    public function reject(int $userId, ?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'decided_by' => $userId,
            'decision_reason' => $reason,
            'decided_at' => now(),
        ]);
    }

    /**
     * Counter offer
     */
    public function counter(float $newPrice, ?array $newTerms = null): void
    {
        $this->update([
            'proposed_price' => $newPrice,
            'terms' => $newTerms ?? $this->terms,
            'round_number' => $this->round_number + 1,
            'status' => 'countered',
        ]);
    }

    /**
     * Withdraw negotiation
     */
    public function withdraw(): void
    {
        $this->update([
            'status' => 'withdrawn',
            'decided_at' => now(),
        ]);
    }

    /**
     * Mark as expired
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
            'decided_at' => now(),
        ]);
    }

    /**
     * Scope: Active negotiations
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where(function($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    /**
     * Scope: For vendor
     */
    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope: For RFQ
     */
    public function scopeForRfq($query, int $rfqId)
    {
        return $query->where('rfq_id', $rfqId);
    }

    /**
     * Scope: Expired negotiations
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
                     ->whereIn('status', ['active', 'countered']);
    }

    /**
     * Scope: By status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}

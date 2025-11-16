<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'rfq_id',
        'quote_number',
        'quoted_by',
        'quoted_at',
        'valid_until',
        'subtotal',
        'tax',
        'total',
        'payment_terms',
        'delivery_days',
        'terms_and_conditions',
        'notes',
        'status',
        'sent_at',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'quoted_at' => 'datetime',
        'valid_until' => 'datetime',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'subtotal' => 'decimal:3',
        'tax' => 'decimal:3',
        'total' => 'decimal:3',
    ];

    // Relationships
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function quotedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quoted_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'sent')
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'sent')
            ->where('valid_until', '<=', now());
    }

    // Helper methods
    public function send(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->rfq->markAsQuoted();
    }

    public function markAsAccepted(): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    public function markAsRejected(?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function isValid(): bool
    {
        return $this->status === 'sent'
            && ($this->valid_until === null || $this->valid_until->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast() && $this->status === 'sent';
    }

    public function canBeAccepted(): bool
    {
        return $this->status === 'sent' && $this->isValid();
    }

    public function canBeRejected(): bool
    {
        return $this->status === 'sent';
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->valid_until || $this->status !== 'sent') {
            return null;
        }

        return now()->diffInDays($this->valid_until, false);
    }

    public function getFormattedPaymentTerms(): string
    {
        return match($this->payment_terms) {
            'immediate' => 'Immediate',
            'net_15' => 'NET 15',
            'net_30' => 'NET 30',
            'net_60' => 'NET 60',
            'net_90' => 'NET 90',
            default => ucfirst($this->payment_terms),
        };
    }

    public function getFormattedDelivery(): ?string
    {
        if (!$this->delivery_days) {
            return null;
        }

        return $this->delivery_days . ' day' . ($this->delivery_days > 1 ? 's' : '');
    }

    public static function generateQuoteNumber(): string
    {
        $year = now()->year;
        $lastQuote = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastQuote ? ((int) substr($lastQuote->quote_number, -5)) + 1 : 1;

        return sprintf('QUO-%d-%05d', $year, $sequence);
    }

    // Events
    protected static function booted(): void
    {
        static::creating(function (RfqQuote $quote) {
            if (!$quote->quote_number) {
                $quote->quote_number = static::generateQuoteNumber();
            }
            if (!$quote->quoted_at) {
                $quote->quoted_at = now();
            }
        });
    }
}

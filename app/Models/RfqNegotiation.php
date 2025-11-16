<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqNegotiation extends Model
{
    use HasFactory;

    protected $fillable = [
        'rfq_id',
        'user_id',
        'message',
        'is_counter_offer',
        'proposed_price',
        'proposed_terms',
        'proposed_delivery_days',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_counter_offer' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'proposed_price' => 'decimal:3',
    ];

    // Relationships
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeCounterOffers($query)
    {
        return $query->where('is_counter_offer', true);
    }

    public function scopeMessages($query)
    {
        return $query->where('is_counter_offer', false);
    }

    public function scopeForRfq($query, $rfqId)
    {
        return $query->where('rfq_id', $rfqId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByOthers($query, $userId)
    {
        return $query->where('user_id', '!=', $userId);
    }

    // Helper methods
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function isFromVendor(): bool
    {
        return $this->user->role === 'vendor';
    }

    public function isFromAdmin(): bool
    {
        return in_array($this->user->role, ['admin', 'super_admin']);
    }

    public function hasCounterOffer(): bool
    {
        return $this->is_counter_offer && $this->proposed_price !== null;
    }

    public function getFormattedProposedTerms(): ?string
    {
        if (!$this->proposed_terms) {
            return null;
        }

        return match($this->proposed_terms) {
            'immediate' => 'Immediate',
            'net_15' => 'NET 15',
            'net_30' => 'NET 30',
            'net_60' => 'NET 60',
            'net_90' => 'NET 90',
            default => ucfirst($this->proposed_terms),
        };
    }

    public function getFormattedProposedDelivery(): ?string
    {
        if (!$this->proposed_delivery_days) {
            return null;
        }

        return $this->proposed_delivery_days . ' day' . ($this->proposed_delivery_days > 1 ? 's' : '');
    }

    // Events
    protected static function booted(): void
    {
        static::created(function (RfqNegotiation $negotiation) {
            // Update RFQ status to negotiating if it's a counter offer
            if ($negotiation->is_counter_offer && $negotiation->rfq->status === 'quoted') {
                $negotiation->rfq->markAsNegotiating();
            }
        });
    }
}

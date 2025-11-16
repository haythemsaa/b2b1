<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NegotiationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'negotiation_id',
        'sender_id',
        'message_type',
        'message',
        'offer_details',
        'offered_price',
        'offered_terms',
        'is_admin_message',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'offer_details' => 'array',
        'offered_terms' => 'array',
        'offered_price' => 'decimal:3',
        'is_admin_message' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the negotiation
     */
    public function negotiation(): BelongsTo
    {
        return $this->belongsTo(PriceNegotiation::class, 'negotiation_id');
    }

    /**
     * Get the sender
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
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
     * Check if message is an offer
     */
    public function isOffer(): bool
    {
        return in_array($this->message_type, ['offer', 'counter_offer']);
    }

    /**
     * Check if message is a decision
     */
    public function isDecision(): bool
    {
        return in_array($this->message_type, ['acceptance', 'rejection']);
    }

    /**
     * Scope: Unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: For negotiation
     */
    public function scopeForNegotiation($query, int $negotiationId)
    {
        return $query->where('negotiation_id', $negotiationId);
    }

    /**
     * Scope: By message type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('message_type', $type);
    }

    /**
     * Scope: Admin messages
     */
    public function scopeAdminMessages($query)
    {
        return $query->where('is_admin_message', true);
    }
}

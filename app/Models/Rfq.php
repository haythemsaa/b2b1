<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rfq extends Model
{
    use HasFactory;

    protected $fillable = [
        'rfq_number',
        'vendor_id',
        'title',
        'description',
        'target_budget',
        'required_delivery_date',
        'status',
        'priority',
        'expires_at',
        'submitted_at',
    ];

    protected $casts = [
        'required_delivery_date' => 'date',
        'target_budget' => 'decimal:3',
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RfqItem::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(RfqQuote::class);
    }

    public function activeQuote(): HasOne
    {
        return $this->hasOne(RfqQuote::class)
            ->where('status', 'sent')
            ->latest();
    }

    public function negotiations(): HasMany
    {
        return $this->hasMany(RfqNegotiation::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeQuoted($query)
    {
        return $query->where('status', 'quoted');
    }

    public function scopeNegotiating($query)
    {
        return $query->where('status', 'negotiating');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['submitted', 'quoted', 'negotiating'])
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->whereNotIn('status', ['accepted', 'rejected', 'converted', 'expired']);
    }

    // Helper methods
    public function submit(): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'submitted', 'quoted', 'negotiating']);
    }

    public function canBeQuoted(): bool
    {
        return $this->status === 'submitted' && !$this->isExpired();
    }

    public function markAsQuoted(): void
    {
        $this->update(['status' => 'quoted']);
    }

    public function markAsNegotiating(): void
    {
        $this->update(['status' => 'negotiating']);
    }

    public function accept(RfqQuote $quote): void
    {
        $this->update([
            'status' => 'accepted',
        ]);

        $quote->markAsAccepted();
    }

    public function reject(RfqQuote $quote, ?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
        ]);

        $quote->markAsRejected($reason);
    }

    public function convertToOrder(): Order
    {
        if ($this->status !== 'accepted') {
            throw new \Exception('Only accepted RFQs can be converted to orders');
        }

        $quote = $this->activeQuote;
        if (!$quote) {
            throw new \Exception('No active quote found');
        }

        // Create order from quote
        $order = Order::create([
            'vendor_id' => $this->vendor_id,
            'order_number' => Order::generateOrderNumber(),
            'subtotal' => $quote->subtotal,
            'tax' => $quote->tax,
            'total' => $quote->total,
            'status' => 'pending',
            'notes' => "Created from RFQ: {$this->rfq_number}",
        ]);

        // Add items to order
        foreach ($this->items as $item) {
            if ($item->product_id) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_sku' => $item->product_sku,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity_requested,
                    'unit_price' => $item->quoted_unit_price,
                    'subtotal' => $item->quoted_subtotal,
                ]);
            }
        }

        $this->update(['status' => 'converted']);

        return $order;
    }

    public function getTotalItemsCount(): int
    {
        return $this->items()->count();
    }

    public function getUnreadNegotiationsCount(User $user): int
    {
        return $this->negotiations()
            ->where('user_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();
    }

    public static function generateRfqNumber(): string
    {
        $year = now()->year;
        $lastRfq = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastRfq ? ((int) substr($lastRfq->rfq_number, -5)) + 1 : 1;

        return sprintf('RFQ-%d-%05d', $year, $sequence);
    }

    // Events
    protected static function booted(): void
    {
        static::creating(function (Rfq $rfq) {
            if (!$rfq->rfq_number) {
                $rfq->rfq_number = static::generateRfqNumber();
            }
        });
    }
}

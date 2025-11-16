<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class OrderPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'product_id',
        'predicted_order_date',
        'predicted_quantity',
        'predicted_amount',
        'confidence_score',
        'average_order_interval_days',
        'average_quantity',
        'quantity_std_dev',
        'historical_order_count',
        'has_seasonal_pattern',
        'seasonal_factors',
        'trend_direction',
        'trend_slope',
        'status',
        'order_id',
        'expires_at',
    ];

    protected $casts = [
        'predicted_order_date' => 'date',
        'predicted_amount' => 'decimal:3',
        'confidence_score' => 'decimal:4',
        'average_order_interval_days' => 'decimal:2',
        'average_quantity' => 'decimal:2',
        'quantity_std_dev' => 'decimal:2',
        'seasonal_factors' => 'array',
        'has_seasonal_pattern' => 'boolean',
        'trend_slope' => 'decimal:4',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the created order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Check if prediction is high confidence
     */
    public function isHighConfidence(): bool
    {
        return $this->confidence_score >= 0.7;
    }

    /**
     * Check if prediction is due soon
     */
    public function isDueSoon(int $days = 7): bool
    {
        return $this->predicted_order_date->isBefore(now()->addDays($days));
    }

    /**
     * Check if prediction is overdue
     */
    public function isOverdue(): bool
    {
        return $this->predicted_order_date->isPast() && $this->status === 'pending';
    }

    /**
     * Check if expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Mark as ordered
     */
    public function markAsOrdered(Order $order): void
    {
        $this->update([
            'status' => 'ordered',
            'order_id' => $order->id,
        ]);
    }

    /**
     * Skip prediction
     */
    public function skip(): void
    {
        $this->update(['status' => 'skipped']);
    }

    /**
     * Mark as expired
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Scope: Pending predictions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: For vendor
     */
    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope: Due within days
     */
    public function scopeDueWithin($query, int $days)
    {
        return $query->where('predicted_order_date', '<=', now()->addDays($days))
                     ->where('predicted_order_date', '>=', now());
    }

    /**
     * Scope: High confidence
     */
    public function scopeHighConfidence($query, float $threshold = 0.7)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    /**
     * Scope: Expired
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<', now())
                     ->where('status', 'pending');
    }
}

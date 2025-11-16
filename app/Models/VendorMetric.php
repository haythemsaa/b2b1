<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'metric_date',
        'period_type',
        'orders_count',
        'orders_total',
        'orders_avg',
        'orders_completed',
        'orders_cancelled',
        'products_viewed',
        'products_added_to_cart',
        'unique_products_ordered',
        'rfqs_submitted',
        'rfqs_quoted',
        'rfqs_accepted',
        'rfqs_conversion_rate',
        'invoices_created',
        'invoices_paid',
        'invoices_total',
        'invoices_paid_total',
        'login_count',
        'page_views',
        'chat_messages',
        'active_sessions',
        'avg_order_processing_time',
        'customer_satisfaction',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'orders_total' => 'decimal:3',
        'orders_avg' => 'decimal:3',
        'rfqs_conversion_rate' => 'decimal:2',
        'invoices_total' => 'decimal:3',
        'invoices_paid_total' => 'decimal:3',
        'avg_order_processing_time' => 'decimal:2',
        'customer_satisfaction' => 'decimal:2',
    ];

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    // Scopes
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeDaily($query)
    {
        return $query->where('period_type', 'daily');
    }

    public function scopeWeekly($query)
    {
        return $query->where('period_type', 'weekly');
    }

    public function scopeMonthly($query)
    {
        return $query->where('period_type', 'monthly');
    }

    public function scopeYearly($query)
    {
        return $query->where('period_type', 'yearly');
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('metric_date', [$startDate, $endDate]);
    }

    public function scopeLatest($query, $limit = 30)
    {
        return $query->orderBy('metric_date', 'desc')->limit($limit);
    }

    // Helper methods
    public function getConversionRate(): float
    {
        if ($this->orders_count == 0) {
            return 0;
        }

        return ($this->orders_completed / $this->orders_count) * 100;
    }

    public function getPaymentRate(): float
    {
        if ($this->invoices_created == 0) {
            return 0;
        }

        return ($this->invoices_paid / $this->invoices_created) * 100;
    }

    public function getEngagementScore(): float
    {
        // Simple engagement score based on activities
        $score = 0;
        $score += min($this->login_count * 5, 30); // Max 30 points for logins
        $score += min($this->page_views * 0.5, 20); // Max 20 points for page views
        $score += min($this->orders_count * 10, 30); // Max 30 points for orders
        $score += min($this->chat_messages * 2, 20); // Max 20 points for chat

        return min($score, 100);
    }

    public static function getOrCreate(int $vendorId, string $date, string $periodType = 'daily'): self
    {
        return static::firstOrCreate(
            [
                'vendor_id' => $vendorId,
                'metric_date' => $date,
                'period_type' => $periodType,
            ],
            [
                // Default values
                'orders_count' => 0,
                'orders_total' => 0,
                'orders_avg' => 0,
                'orders_completed' => 0,
                'orders_cancelled' => 0,
                'products_viewed' => 0,
                'products_added_to_cart' => 0,
                'unique_products_ordered' => 0,
                'rfqs_submitted' => 0,
                'rfqs_quoted' => 0,
                'rfqs_accepted' => 0,
                'rfqs_conversion_rate' => 0,
                'invoices_created' => 0,
                'invoices_paid' => 0,
                'invoices_total' => 0,
                'invoices_paid_total' => 0,
                'login_count' => 0,
                'page_views' => 0,
                'chat_messages' => 0,
                'active_sessions' => 0,
                'avg_order_processing_time' => 0,
            ]
        );
    }

    public function incrementMetric(string $metric, $value = 1): void
    {
        $this->increment($metric, $value);
    }

    public function updateAverage(): void
    {
        if ($this->orders_count > 0) {
            $this->update([
                'orders_avg' => $this->orders_total / $this->orders_count,
            ]);
        }

        if ($this->rfqs_submitted > 0) {
            $this->update([
                'rfqs_conversion_rate' => ($this->rfqs_accepted / $this->rfqs_submitted) * 100,
            ]);
        }
    }
}

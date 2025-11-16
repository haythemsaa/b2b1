<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'vendor_id',
        'user_id',
        'event_type',
        'event_category',
        'event_action',
        'event_label',
        'event_data',
        'event_value',
        'session_id',
        'user_agent',
        'ip_address',
        'referrer',
        'url',
        'event_time',
    ];

    protected $casts = [
        'event_data' => 'array',
        'event_value' => 'decimal:3',
        'event_time' => 'datetime',
    ];

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('event_category', $category);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('event_time', [$startDate, $endDate]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('event_time', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('event_time', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('event_time', now()->month)
            ->whereYear('event_time', now()->year);
    }

    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    // Helper methods
    public static function track(
        string $eventType,
        ?int $vendorId = null,
        ?int $userId = null,
        ?string $category = null,
        ?string $action = null,
        ?string $label = null,
        ?array $data = null,
        ?float $value = null
    ): self {
        return static::create([
            'vendor_id' => $vendorId,
            'user_id' => $userId,
            'event_type' => $eventType,
            'event_category' => $category,
            'event_action' => $action,
            'event_label' => $label,
            'event_data' => $data,
            'event_value' => $value,
            'session_id' => session()->getId(),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
            'referrer' => request()->headers->get('referer'),
            'url' => request()->fullUrl(),
            'event_time' => now(),
        ]);
    }

    public static function trackPageView(?int $vendorId = null, ?int $userId = null): self
    {
        return static::track('page_view', $vendorId, $userId, 'navigation', 'view', request()->path());
    }

    public static function trackProductView(int $productId, ?int $vendorId = null, ?int $userId = null): self
    {
        return static::track(
            'product_view',
            $vendorId,
            $userId,
            'products',
            'view',
            "Product #{$productId}",
            ['product_id' => $productId]
        );
    }

    public static function trackOrderCreated(int $orderId, float $total, ?int $vendorId = null, ?int $userId = null): self
    {
        return static::track(
            'order_created',
            $vendorId,
            $userId,
            'orders',
            'create',
            "Order #{$orderId}",
            ['order_id' => $orderId],
            $total
        );
    }
}

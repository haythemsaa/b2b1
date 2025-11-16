<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\VendorMetric;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    /**
     * Track an event
     */
    public function trackEvent(
        string $eventType,
        ?int $vendorId = null,
        ?int $userId = null,
        ?string $category = null,
        ?string $action = null,
        ?string $label = null,
        ?array $data = null,
        ?float $value = null
    ): AnalyticsEvent {
        return AnalyticsEvent::track(
            $eventType,
            $vendorId,
            $userId,
            $category,
            $action,
            $label,
            $data,
            $value
        );
    }

    /**
     * Get vendor dashboard metrics
     */
    public function getDashboard(User $vendor, string $period = 'monthly', int $limit = 30): array
    {
        $metrics = VendorMetric::forVendor($vendor->id)
            ->where('period_type', $period)
            ->latest($limit)
            ->get();

        // Calculate trends
        $current = $metrics->first();
        $previous = $metrics->skip(1)->first();

        return [
            'current_period' => $this->formatMetrics($current),
            'previous_period' => $this->formatMetrics($previous),
            'trends' => $this->calculateTrends($current, $previous),
            'historical' => $metrics->map(fn($m) => $this->formatMetrics($m)),
            'summary' => $this->getSummaryMetrics($vendor, $period),
        ];
    }

    /**
     * Format metrics for response
     */
    protected function formatMetrics(?VendorMetric $metric): ?array
    {
        if (!$metric) {
            return null;
        }

        return [
            'date' => $metric->metric_date->format('Y-m-d'),
            'period_type' => $metric->period_type,
            'orders' => [
                'count' => $metric->orders_count,
                'total' => (float) $metric->orders_total,
                'average' => (float) $metric->orders_avg,
                'completed' => $metric->orders_completed,
                'cancelled' => $metric->orders_cancelled,
                'conversion_rate' => $metric->getConversionRate(),
            ],
            'products' => [
                'viewed' => $metric->products_viewed,
                'added_to_cart' => $metric->products_added_to_cart,
                'unique_ordered' => $metric->unique_products_ordered,
            ],
            'rfqs' => [
                'submitted' => $metric->rfqs_submitted,
                'quoted' => $metric->rfqs_quoted,
                'accepted' => $metric->rfqs_accepted,
                'conversion_rate' => (float) $metric->rfqs_conversion_rate,
            ],
            'invoices' => [
                'created' => $metric->invoices_created,
                'paid' => $metric->invoices_paid,
                'total' => (float) $metric->invoices_total,
                'paid_total' => (float) $metric->invoices_paid_total,
                'payment_rate' => $metric->getPaymentRate(),
            ],
            'engagement' => [
                'logins' => $metric->login_count,
                'page_views' => $metric->page_views,
                'chat_messages' => $metric->chat_messages,
                'active_sessions' => $metric->active_sessions,
                'engagement_score' => $metric->getEngagementScore(),
            ],
            'performance' => [
                'avg_processing_time' => (float) $metric->avg_order_processing_time,
                'customer_satisfaction' => (float) $metric->customer_satisfaction,
            ],
        ];
    }

    /**
     * Calculate trends between periods
     */
    protected function calculateTrends(?VendorMetric $current, ?VendorMetric $previous): array
    {
        if (!$current || !$previous) {
            return [];
        }

        return [
            'orders_count' => $this->calculateChange($current->orders_count, $previous->orders_count),
            'orders_total' => $this->calculateChange($current->orders_total, $previous->orders_total),
            'invoices_paid' => $this->calculateChange($current->invoices_paid, $previous->invoices_paid),
            'engagement_score' => $this->calculateChange(
                $current->getEngagementScore(),
                $previous->getEngagementScore()
            ),
        ];
    }

    /**
     * Calculate percentage change
     */
    protected function calculateChange(float $current, float $previous): array
    {
        if ($previous == 0) {
            return [
                'value' => $current,
                'change' => 0,
                'direction' => 'neutral',
            ];
        }

        $change = (($current - $previous) / $previous) * 100;

        return [
            'value' => $current,
            'change' => round($change, 2),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
        ];
    }

    /**
     * Get summary metrics for vendor
     */
    protected function getSummaryMetrics(User $vendor, string $period): array
    {
        $startDate = match($period) {
            'daily' => now()->subDays(30),
            'weekly' => now()->subWeeks(12),
            'monthly' => now()->subMonths(12),
            'yearly' => now()->subYears(5),
            default => now()->subMonths(12),
        };

        $metrics = VendorMetric::forVendor($vendor->id)
            ->where('period_type', $period)
            ->where('metric_date', '>=', $startDate)
            ->get();

        return [
            'total_orders' => $metrics->sum('orders_count'),
            'total_revenue' => (float) $metrics->sum('orders_total'),
            'total_rfqs' => $metrics->sum('rfqs_submitted'),
            'total_invoices' => $metrics->sum('invoices_created'),
            'avg_order_value' => $metrics->avg('orders_avg'),
            'avg_engagement' => $metrics->avg(fn($m) => $m->getEngagementScore()),
        ];
    }

    /**
     * Update daily metrics for vendor
     */
    public function updateDailyMetrics(User $vendor, ?\Carbon\Carbon $date = null): VendorMetric
    {
        $date = $date ?: now();
        $dateString = $date->toDateString();

        $metric = VendorMetric::getOrCreate($vendor->id, $dateString, 'daily');

        // Calculate orders metrics
        $orders = Order::where('vendor_id', $vendor->id)
            ->whereDate('created_at', $date)
            ->get();

        $metric->update([
            'orders_count' => $orders->count(),
            'orders_total' => $orders->sum('total'),
            'orders_completed' => $orders->where('status', 'delivered')->count(),
            'orders_cancelled' => $orders->where('status', 'cancelled')->count(),
        ]);

        // Calculate events metrics
        $events = AnalyticsEvent::forVendor($vendor->id)
            ->whereDate('event_time', $date)
            ->get();

        $metric->update([
            'page_views' => $events->where('event_type', 'page_view')->count(),
            'products_viewed' => $events->where('event_type', 'product_view')->count(),
            'login_count' => $events->where('event_type', 'login')->count(),
        ]);

        $metric->updateAverage();

        Log::info('Daily metrics updated', [
            'vendor_id' => $vendor->id,
            'date' => $dateString,
        ]);

        return $metric;
    }

    /**
     * Aggregate metrics to weekly/monthly/yearly
     */
    public function aggregateMetrics(User $vendor, string $periodType, \Carbon\Carbon $date): VendorMetric
    {
        $startDate = match($periodType) {
            'weekly' => $date->copy()->startOfWeek(),
            'monthly' => $date->copy()->startOfMonth(),
            'yearly' => $date->copy()->startOfYear(),
            default => $date,
        };

        $endDate = match($periodType) {
            'weekly' => $date->copy()->endOfWeek(),
            'monthly' => $date->copy()->endOfMonth(),
            'yearly' => $date->copy()->endOfYear(),
            default => $date,
        };

        $dailyMetrics = VendorMetric::forVendor($vendor->id)
            ->daily()
            ->inDateRange($startDate, $endDate)
            ->get();

        $aggregated = VendorMetric::getOrCreate($vendor->id, $startDate->toDateString(), $periodType);

        $aggregated->update([
            'orders_count' => $dailyMetrics->sum('orders_count'),
            'orders_total' => $dailyMetrics->sum('orders_total'),
            'orders_completed' => $dailyMetrics->sum('orders_completed'),
            'orders_cancelled' => $dailyMetrics->sum('orders_cancelled'),
            'products_viewed' => $dailyMetrics->sum('products_viewed'),
            'rfqs_submitted' => $dailyMetrics->sum('rfqs_submitted'),
            'rfqs_quoted' => $dailyMetrics->sum('rfqs_quoted'),
            'rfqs_accepted' => $dailyMetrics->sum('rfqs_accepted'),
            'invoices_created' => $dailyMetrics->sum('invoices_created'),
            'invoices_paid' => $dailyMetrics->sum('invoices_paid'),
            'invoices_total' => $dailyMetrics->sum('invoices_total'),
            'invoices_paid_total' => $dailyMetrics->sum('invoices_paid_total'),
            'page_views' => $dailyMetrics->sum('page_views'),
            'login_count' => $dailyMetrics->sum('login_count'),
            'chat_messages' => $dailyMetrics->sum('chat_messages'),
        ]);

        $aggregated->updateAverage();

        Log::info('Metrics aggregated', [
            'vendor_id' => $vendor->id,
            'period_type' => $periodType,
            'date' => $startDate->toDateString(),
        ]);

        return $aggregated;
    }

    /**
     * Get event statistics
     */
    public function getEventStats(User $vendor, string $period = 'week'): array
    {
        $startDate = match($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfWeek(),
        };

        $events = AnalyticsEvent::forVendor($vendor->id)
            ->where('event_time', '>=', $startDate)
            ->get();

        return [
            'total_events' => $events->count(),
            'by_type' => $events->groupBy('event_type')->map->count(),
            'by_category' => $events->groupBy('event_category')->map->count(),
            'total_value' => (float) $events->sum('event_value'),
            'unique_sessions' => $events->pluck('session_id')->unique()->count(),
        ];
    }

    /**
     * Get top performing products
     */
    public function getTopProducts(User $vendor, int $limit = 10): array
    {
        $events = AnalyticsEvent::forVendor($vendor->id)
            ->byType('product_view')
            ->thisMonth()
            ->get();

        return $events->groupBy('event_data.product_id')
            ->map(function ($productEvents) {
                return [
                    'product_id' => $productEvents->first()->event_data['product_id'] ?? null,
                    'views' => $productEvents->count(),
                    'unique_sessions' => $productEvents->pluck('session_id')->unique()->count(),
                ];
            })
            ->sortByDesc('views')
            ->take($limit)
            ->values()
            ->toArray();
    }
}

<?php

namespace App\Services;

use App\Models\OrderPrediction;
use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;

class PredictiveOrderingService
{
    public function generatePredictions(User $vendor): int
    {
        $products = Product::where('vendor_id', $vendor->id)->get();
        $count = 0;

        foreach ($products as $product) {
            if ($prediction = $this->predictNextOrder($vendor, $product)) {
                $count++;
            }
        }

        return $count;
    }

    protected function predictNextOrder(User $vendor, Product $product): ?OrderPrediction
    {
        $orderHistory = OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn($q) => $q->where('vendor_id', $vendor->id)->where('status', 'completed'))
            ->with('order')
            ->orderBy('created_at')
            ->get();

        if ($orderHistory->count() < 2) {
            return null;
        }

        $intervals = [];
        $quantities = [];
        $previous = null;

        foreach ($orderHistory as $item) {
            $quantities[] = $item->quantity;
            
            if ($previous) {
                $intervals[] = $previous->order->created_at->diffInDays($item->order->created_at);
            }
            $previous = $item;
        }

        $avgInterval = collect($intervals)->average();
        $avgQuantity = collect($quantities)->average();
        $stdDev = $this->calculateStdDev($quantities);

        $lastOrderDate = $orderHistory->last()->order->created_at;
        $predictedDate = $lastOrderDate->addDays((int)$avgInterval);
        
        $confidence = min(1.0, $orderHistory->count() / 10) * (1 - min($stdDev / max($avgQuantity, 1), 0.5));

        return OrderPrediction::updateOrCreate(
            ['vendor_id' => $vendor->id, 'product_id' => $product->id],
            [
                'predicted_order_date' => $predictedDate,
                'predicted_quantity' => (int)$avgQuantity,
                'predicted_amount' => $product->price * $avgQuantity,
                'confidence_score' => $confidence,
                'average_order_interval_days' => $avgInterval,
                'average_quantity' => $avgQuantity,
                'quantity_std_dev' => $stdDev,
                'historical_order_count' => $orderHistory->count(),
                'status' => 'pending',
                'expires_at' => $predictedDate->addDays(14),
            ]
        );
    }

    protected function calculateStdDev(array $values): float
    {
        $avg = collect($values)->average();
        $variance = collect($values)->map(fn($v) => pow($v - $avg, 2))->average();
        return sqrt($variance);
    }

    public function getPredictions(User $vendor, ?string $status = 'pending', int $limit = 50)
    {
        $query = OrderPrediction::forVendor($vendor->id)
            ->with('product')
            ->orderBy('predicted_order_date');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->limit($limit)->get();
    }
}

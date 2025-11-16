<?php

namespace App\Services;

use App\Models\ProductRecommendation;
use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class RecommendationService
{
    /**
     * Get recommendations for product
     */
    public function getRecommendations(
        Product $product,
        ?string $type = null,
        int $limit = 10,
        float $minConfidence = 0.3
    ): Collection {
        $query = ProductRecommendation::forProduct($product->id)
            ->active()
            ->highConfidence($minConfidence)
            ->byConfidence()
            ->with('recommendedProduct')
            ->limit($limit);

        if ($type) {
            $query->byType($type);
        }

        return $query->get()->map->recommendedProduct;
    }

    /**
     * Get personalized recommendations for vendor
     */
    public function getPersonalizedRecommendations(
        User $vendor,
        int $limit = 20
    ): Collection {
        // Get vendor's order history
        $orderedProductIds = OrderItem::whereHas('order', function($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        })->distinct('product_id')->pluck('product_id');

        if ($orderedProductIds->isEmpty()) {
            return $this->getTrendingProducts($vendor, $limit);
        }

        // Get recommendations for ordered products
        $recommendations = ProductRecommendation::whereIn('product_id', $orderedProductIds)
            ->active()
            ->highConfidence(0.5)
            ->byType('personalized')
            ->byConfidence()
            ->with('recommendedProduct')
            ->limit($limit * 2)
            ->get();

        // Filter out already ordered products
        $recommendations = $recommendations->filter(function($rec) use ($orderedProductIds) {
            return !$orderedProductIds->contains($rec->recommended_product_id);
        });

        return $recommendations->take($limit)->map->recommendedProduct;
    }

    /**
     * Get frequently bought together
     */
    public function getFrequentlyBoughtTogether(
        Product $product,
        int $limit = 5
    ): Collection {
        return $this->getRecommendations(
            $product,
            'frequently_bought_together',
            $limit,
            0.4
        );
    }

    /**
     * Get similar products
     */
    public function getSimilarProducts(
        Product $product,
        int $limit = 10
    ): Collection {
        return $this->getRecommendations(
            $product,
            'similar_products',
            $limit,
            0.3
        );
    }

    /**
     * Get trending products
     */
    public function getTrendingProducts(
        User $vendor,
        int $limit = 20
    ): Collection {
        return Product::where('vendor_id', $vendor->id)
            ->withCount(['orderItems' => function($q) {
                $q->where('created_at', '>=', now()->subDays(30));
            }])
            ->orderBy('order_items_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate recommendations for vendor
     */
    public function calculateRecommendations(User $vendor): int
    {
        $count = 0;

        // Calculate frequently bought together
        $count += $this->calculateFrequentlyBoughtTogether($vendor);

        // Calculate similar products
        $count += $this->calculateSimilarProducts($vendor);

        // Calculate trending
        $count += $this->calculateTrending($vendor);

        return $count;
    }

    /**
     * Calculate frequently bought together
     */
    protected function calculateFrequentlyBoughtTogether(User $vendor): int
    {
        // Get all orders for vendor
        $orders = Order::where('vendor_id', $vendor->id)
            ->where('status', 'completed')
            ->with('items')
            ->get();

        // Build co-occurrence matrix
        $coOccurrences = [];
        $productCounts = [];

        foreach ($orders as $order) {
            $productIds = $order->items->pluck('product_id')->toArray();

            foreach ($productIds as $productId) {
                $productCounts[$productId] = ($productCounts[$productId] ?? 0) + 1;

                foreach ($productIds as $otherProductId) {
                    if ($productId != $otherProductId) {
                        $key = $productId < $otherProductId
                            ? "{$productId}-{$otherProductId}"
                            : "{$otherProductId}-{$productId}";

                        $coOccurrences[$key] = ($coOccurrences[$key] ?? 0) + 1;
                    }
                }
            }
        }

        // Calculate confidence and lift
        $count = 0;
        $totalOrders = $orders->count();

        foreach ($coOccurrences as $key => $supportCount) {
            if ($supportCount < 2) continue; // Minimum support

            [$productId1, $productId2] = explode('-', $key);

            $support = $supportCount / $totalOrders;
            $confidence1 = $supportCount / ($productCounts[$productId1] ?? 1);
            $confidence2 = $supportCount / ($productCounts[$productId2] ?? 1);

            $expectedCo = ($productCounts[$productId1] / $totalOrders) *
                         ($productCounts[$productId2] / $totalOrders);
            $lift = $support / $expectedCo;

            // Create bidirectional recommendations
            if ($confidence1 >= 0.3) {
                $this->createOrUpdateRecommendation(
                    $vendor->id,
                    $productId1,
                    $productId2,
                    'frequently_bought_together',
                    $confidence1,
                    $supportCount,
                    $lift
                );
                $count++;
            }

            if ($confidence2 >= 0.3) {
                $this->createOrUpdateRecommendation(
                    $vendor->id,
                    $productId2,
                    $productId1,
                    'frequently_bought_together',
                    $confidence2,
                    $supportCount,
                    $lift
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Calculate similar products (based on category and attributes)
     */
    protected function calculateSimilarProducts(User $vendor): int
    {
        $products = Product::where('vendor_id', $vendor->id)->get();
        $count = 0;

        foreach ($products as $product) {
            // Find similar by category
            $similarByCategory = Product::where('vendor_id', $vendor->id)
                ->where('category', $product->category)
                ->where('id', '!=', $product->id)
                ->limit(10)
                ->get();

            foreach ($similarByCategory as $similar) {
                $confidence = 0.5; // Base confidence for same category

                // Increase confidence based on price similarity
                if ($product->price > 0) {
                    $priceDiff = abs($product->price - $similar->price) / $product->price;
                    $confidence += (1 - min($priceDiff, 1)) * 0.3;
                }

                $this->createOrUpdateRecommendation(
                    $vendor->id,
                    $product->id,
                    $similar->id,
                    'similar_products',
                    $confidence,
                    1,
                    1.0
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Calculate trending products
     */
    protected function calculateTrending(User $vendor): int
    {
        $products = Product::where('vendor_id', $vendor->id)->get();
        $count = 0;

        // Get trending products (most ordered in last 30 days)
        $trending = Product::where('vendor_id', $vendor->id)
            ->withCount(['orderItems' => function($q) {
                $q->where('created_at', '>=', now()->subDays(30));
            }])
            ->having('order_items_count', '>', 5)
            ->orderBy('order_items_count', 'desc')
            ->limit(20)
            ->get();

        if ($trending->isEmpty()) {
            return 0;
        }

        // Recommend trending products to all other products
        foreach ($products as $product) {
            foreach ($trending as $trendingProduct) {
                if ($product->id == $trendingProduct->id) continue;

                $confidence = min($trendingProduct->order_items_count / 50, 0.9);

                $this->createOrUpdateRecommendation(
                    $vendor->id,
                    $product->id,
                    $trendingProduct->id,
                    'trending',
                    $confidence,
                    $trendingProduct->order_items_count,
                    1.0
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Create or update recommendation
     */
    protected function createOrUpdateRecommendation(
        int $vendorId,
        int $productId,
        int $recommendedProductId,
        string $type,
        float $confidence,
        int $supportCount,
        float $lift
    ): void {
        ProductRecommendation::updateOrCreate(
            [
                'vendor_id' => $vendorId,
                'product_id' => $productId,
                'recommended_product_id' => $recommendedProductId,
                'recommendation_type' => $type,
            ],
            [
                'confidence_score' => $confidence,
                'support_count' => $supportCount,
                'lift' => $lift,
                'is_active' => true,
                'last_calculated_at' => now(),
            ]
        );
    }

    /**
     * Get recommendation statistics
     */
    public function getStats(User $vendor): array
    {
        $recommendations = ProductRecommendation::where('vendor_id', $vendor->id)->get();

        return [
            'total_recommendations' => $recommendations->count(),
            'by_type' => $recommendations->groupBy('recommendation_type')->map->count(),
            'active_recommendations' => $recommendations->where('is_active', true)->count(),
            'high_confidence' => $recommendations->where('confidence_score', '>=', 0.7)->count(),
            'medium_confidence' => $recommendations->whereBetween('confidence_score', [0.3, 0.7])->count(),
            'low_confidence' => $recommendations->where('confidence_score', '<', 0.3)->count(),
            'last_calculated' => $recommendations->max('last_calculated_at'),
        ];
    }
}

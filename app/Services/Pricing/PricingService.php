<?php

namespace App\Services\Pricing;

use App\Models\Product;
use App\Models\User;
use App\Models\ProductPricing;
use App\Models\Promotion;
use Illuminate\Support\Collection;

class PricingService
{
    /**
     * Calculate the final price for a product for a specific vendor and quantity
     */
    public function calculatePrice(Product $product, User $vendor, int $quantity = 1): array
    {
        if (!$vendor->isVendor()) {
            throw new \InvalidArgumentException('User must be a vendor');
        }

        // Get base price considering vendor-specific or group pricing
        $basePrice = $this->getApplicablePrice($product, $vendor, $quantity);

        // Apply active promotions
        $promotionDiscount = $this->calculatePromotionDiscount($product, $vendor, $basePrice, $quantity);

        // Calculate final price
        $finalPrice = $basePrice - $promotionDiscount;
        $finalPrice = max(0, round($finalPrice, 3));

        return [
            'base_price' => round($basePrice, 3),
            'promotion_discount' => round($promotionDiscount, 3),
            'final_price' => $finalPrice,
            'total' => round($finalPrice * $quantity, 3),
            'quantity' => $quantity,
        ];
    }

    /**
     * Get the applicable price for a vendor (individual > group > base)
     */
    protected function getApplicablePrice(Product $product, User $vendor, int $quantity): float
    {
        $vendorProfile = $vendor->vendorProfile;

        // Priority 1: Individual vendor pricing for the quantity range
        $vendorPrice = ProductPricing::where('product_id', $product->id)
            ->where('vendor_id', $vendor->id)
            ->where('min_quantity', '<=', $quantity)
            ->where(function($q) use ($quantity) {
                $q->whereNull('max_quantity')
                  ->orWhere('max_quantity', '>=', $quantity);
            })
            ->orderBy('min_quantity', 'desc')
            ->first();

        if ($vendorPrice) {
            return $vendorPrice->getFinalPrice();
        }

        // Priority 2: Vendor group pricing
        if ($vendorProfile && $vendorProfile->vendor_group_id) {
            $groupPrice = ProductPricing::where('product_id', $product->id)
                ->where('vendor_group_id', $vendorProfile->vendor_group_id)
                ->where('min_quantity', '<=', $quantity)
                ->where(function($q) use ($quantity) {
                    $q->whereNull('max_quantity')
                      ->orWhere('max_quantity', '>=', $quantity);
                })
                ->orderBy('min_quantity', 'desc')
                ->first();

            if ($groupPrice) {
                return $groupPrice->getFinalPrice();
            }

            // Apply vendor group default discount to base price
            if ($vendorProfile->vendorGroup && $vendorProfile->vendorGroup->default_discount_percentage > 0) {
                $discount = $vendorProfile->vendorGroup->default_discount_percentage;
                return $product->base_price * (1 - $discount / 100);
            }
        }

        // Priority 3: Base product price
        return $product->base_price;
    }

    /**
     * Calculate promotion discount if applicable
     */
    protected function calculatePromotionDiscount(Product $product, User $vendor, float $price, int $quantity): float
    {
        $vendorProfile = $vendor->vendorProfile;

        // Get active promotions applicable to this product and vendor
        $promotions = Promotion::active()
            ->current()
            ->whereHas('eligibility', function($q) use ($product, $vendor, $vendorProfile) {
                $q->where(function($query) use ($product) {
                    // Product-specific or category-specific or all products
                    $query->where('product_id', $product->id)
                          ->orWhere('category_id', $product->category_id)
                          ->orWhereNull('product_id');
                })
                ->where(function($query) use ($vendor, $vendorProfile) {
                    // Vendor-specific or group-specific or all vendors
                    $query->where('vendor_id', $vendor->id)
                          ->orWhere('vendor_group_id', $vendorProfile?->vendor_group_id)
                          ->orWhereNull('vendor_id');
                });
            })
            ->get();

        // Apply the best promotion
        $maxDiscount = 0;
        foreach ($promotions as $promotion) {
            $discount = $promotion->calculateDiscount($price);
            $maxDiscount = max($maxDiscount, $discount);
        }

        return $maxDiscount;
    }

    /**
     * Calculate cart total with all items and discounts
     */
    public function calculateCartTotal(User $vendor, array $cartItems): array
    {
        if (!$vendor->isVendor()) {
            throw new \InvalidArgumentException('User must be a vendor');
        }

        $items = [];
        $subtotal = 0;
        $totalPromotionDiscount = 0;
        $totalQuantity = 0;

        foreach ($cartItems as $item) {
            $product = Product::find($item['product_id']);
            if (!$product || !$product->is_active) {
                continue;
            }

            $quantity = $item['quantity'] ?? 1;

            // Validate order constraints
            if (!$product->canOrder($quantity)) {
                throw new \InvalidArgumentException(
                    "Product {$product->sku} cannot be ordered with quantity {$quantity}"
                );
            }

            $pricing = $this->calculatePrice($product, $vendor, $quantity);

            $items[] = [
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'product_name' => $product->name_fr,
                'quantity' => $quantity,
                'unit_price' => $pricing['base_price'],
                'promotion_discount' => $pricing['promotion_discount'],
                'final_unit_price' => $pricing['final_price'],
                'subtotal' => $pricing['total'],
            ];

            $subtotal += $pricing['base_price'] * $quantity;
            $totalPromotionDiscount += $pricing['promotion_discount'] * $quantity;
            $totalQuantity += $quantity;
        }

        $total = round($subtotal - $totalPromotionDiscount, 3);

        // Check vendor's minimum order amount
        $vendorProfile = $vendor->vendorProfile;
        $minimumOrderAmount = $vendorProfile?->minimum_order_amount ?? 0;

        return [
            'items' => $items,
            'subtotal' => round($subtotal, 3),
            'total_promotion_discount' => round($totalPromotionDiscount, 3),
            'total' => $total,
            'total_quantity' => $totalQuantity,
            'minimum_order_amount' => $minimumOrderAmount,
            'meets_minimum' => $total >= $minimumOrderAmount,
        ];
    }

    /**
     * Get all pricing tiers for a product and vendor
     */
    public function getPricingTiers(Product $product, User $vendor): Collection
    {
        $vendorProfile = $vendor->vendorProfile;

        // Get vendor-specific pricing tiers
        $vendorTiers = ProductPricing::where('product_id', $product->id)
            ->where('vendor_id', $vendor->id)
            ->orderBy('min_quantity')
            ->get();

        if ($vendorTiers->isNotEmpty()) {
            return $vendorTiers->map(fn($tier) => [
                'min_quantity' => $tier->min_quantity,
                'max_quantity' => $tier->max_quantity,
                'price' => $tier->getFinalPrice(),
                'discount_percentage' => $tier->discount_percentage,
                'type' => 'vendor_specific',
            ]);
        }

        // Get group pricing tiers
        if ($vendorProfile && $vendorProfile->vendor_group_id) {
            $groupTiers = ProductPricing::where('product_id', $product->id)
                ->where('vendor_group_id', $vendorProfile->vendor_group_id)
                ->orderBy('min_quantity')
                ->get();

            if ($groupTiers->isNotEmpty()) {
                return $groupTiers->map(fn($tier) => [
                    'min_quantity' => $tier->min_quantity,
                    'max_quantity' => $tier->max_quantity,
                    'price' => $tier->getFinalPrice(),
                    'discount_percentage' => $tier->discount_percentage,
                    'type' => 'group',
                ]);
            }
        }

        // Return base price as single tier
        return collect([[
            'min_quantity' => $product->minimum_order_quantity,
            'max_quantity' => null,
            'price' => $product->base_price,
            'discount_percentage' => 0,
            'type' => 'base',
        ]]);
    }

    /**
     * Set custom pricing for a vendor
     */
    public function setVendorPricing(Product $product, User $vendor, array $pricingData): ProductPricing
    {
        return ProductPricing::updateOrCreate(
            [
                'product_id' => $product->id,
                'vendor_id' => $vendor->id,
                'min_quantity' => $pricingData['min_quantity'] ?? 1,
            ],
            [
                'price' => $pricingData['price'],
                'discount_percentage' => $pricingData['discount_percentage'] ?? 0,
                'max_quantity' => $pricingData['max_quantity'] ?? null,
            ]
        );
    }

    /**
     * Set pricing for a vendor group
     */
    public function setGroupPricing(Product $product, int $vendorGroupId, array $pricingData): ProductPricing
    {
        return ProductPricing::updateOrCreate(
            [
                'product_id' => $product->id,
                'vendor_group_id' => $vendorGroupId,
                'min_quantity' => $pricingData['min_quantity'] ?? 1,
            ],
            [
                'price' => $pricingData['price'],
                'discount_percentage' => $pricingData['discount_percentage'] ?? 0,
                'max_quantity' => $pricingData['max_quantity'] ?? null,
            ]
        );
    }

    /**
     * Delete vendor-specific pricing
     */
    public function deleteVendorPricing(Product $product, User $vendor, ?int $minQuantity = null): int
    {
        $query = ProductPricing::where('product_id', $product->id)
            ->where('vendor_id', $vendor->id);

        if ($minQuantity !== null) {
            $query->where('min_quantity', $minQuantity);
        }

        return $query->delete();
    }
}

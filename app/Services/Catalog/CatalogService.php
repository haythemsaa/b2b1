<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Models\ProductVendorVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class CatalogService
{
    /**
     * Get products visible to a specific vendor
     */
    public function getVisibleProductsForVendor(User $vendor, array $filters = []): Builder
    {
        if (!$vendor->isVendor()) {
            throw new \InvalidArgumentException('User must be a vendor');
        }

        $vendorProfile = $vendor->vendorProfile;

        $query = Product::active()
            ->with(['category', 'primaryImage'])
            ->where(function($q) use ($vendor, $vendorProfile) {
                // Products with no visibility restrictions
                $q->whereDoesntHave('visibility')
                  // OR visible to this specific vendor
                  ->orWhereHas('visibility', function($vq) use ($vendor) {
                      $vq->where('vendor_id', $vendor->id)
                         ->where('is_visible', true);
                  });

                // OR visible to vendor's group
                if ($vendorProfile && $vendorProfile->vendor_group_id) {
                    $q->orWhereHas('visibility', function($vq) use ($vendorProfile) {
                        $vq->where('vendor_group_id', $vendorProfile->vendor_group_id)
                           ->where('is_visible', true)
                           ->whereNull('vendor_id'); // Group-level only
                    });
                }
            })
            // Exclude products explicitly hidden from this vendor
            ->whereDoesntHave('visibility', function($vq) use ($vendor) {
                $vq->where('vendor_id', $vendor->id)
                   ->where('is_visible', false);
            });

        // Apply filters
        if (!empty($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name_fr', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (isset($filters['in_stock']) && $filters['in_stock']) {
            $query->inStock();
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        switch ($sortBy) {
            case 'price':
                $query->orderBy('base_price', $sortOrder);
                break;
            case 'stock':
                $query->orderBy('stock_quantity', $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            default:
                $query->orderBy('name_fr', $sortOrder);
        }

        return $query;
    }

    /**
     * Check if a product is visible to a vendor
     */
    public function isProductVisible(Product $product, User $vendor): bool
    {
        if (!$vendor->isVendor()) {
            return false;
        }

        if (!$product->is_active) {
            return false;
        }

        $vendorProfile = $vendor->vendorProfile;

        // Check for explicit vendor-level visibility (takes precedence)
        $vendorVisibility = ProductVendorVisibility::where('product_id', $product->id)
            ->where('vendor_id', $vendor->id)
            ->first();

        if ($vendorVisibility) {
            return $vendorVisibility->is_visible;
        }

        // Check for group-level visibility
        if ($vendorProfile && $vendorProfile->vendor_group_id) {
            $groupVisibility = ProductVendorVisibility::where('product_id', $product->id)
                ->where('vendor_group_id', $vendorProfile->vendor_group_id)
                ->whereNull('vendor_id')
                ->first();

            if ($groupVisibility) {
                return $groupVisibility->is_visible;
            }
        }

        // If no visibility rules, product is visible by default
        return true;
    }

    /**
     * Set product visibility for a specific vendor
     */
    public function setProductVisibilityForVendor(Product $product, User $vendor, bool $isVisible): ProductVendorVisibility
    {
        return ProductVendorVisibility::updateOrCreate(
            [
                'product_id' => $product->id,
                'vendor_id' => $vendor->id,
            ],
            [
                'is_visible' => $isVisible,
            ]
        );
    }

    /**
     * Set product visibility for a vendor group
     */
    public function setProductVisibilityForGroup(Product $product, int $vendorGroupId, bool $isVisible): ProductVendorVisibility
    {
        return ProductVendorVisibility::updateOrCreate(
            [
                'product_id' => $product->id,
                'vendor_group_id' => $vendorGroupId,
                'vendor_id' => null,
            ],
            [
                'is_visible' => $isVisible,
            ]
        );
    }

    /**
     * Bulk set visibility for multiple products to a vendor
     */
    public function bulkSetVisibilityForVendor(array $productIds, User $vendor, bool $isVisible): int
    {
        $count = 0;
        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            if ($product) {
                $this->setProductVisibilityForVendor($product, $vendor, $isVisible);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Bulk set visibility for multiple products to a group
     */
    public function bulkSetVisibilityForGroup(array $productIds, int $vendorGroupId, bool $isVisible): int
    {
        $count = 0;
        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            if ($product) {
                $this->setProductVisibilityForGroup($product, $vendorGroupId, $isVisible);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Remove visibility restrictions for a vendor
     */
    public function removeVendorVisibilityRestrictions(Product $product, User $vendor): int
    {
        return ProductVendorVisibility::where('product_id', $product->id)
            ->where('vendor_id', $vendor->id)
            ->delete();
    }

    /**
     * Get all categories with product counts for a vendor
     */
    public function getCategoriesForVendor(User $vendor): \Illuminate\Support\Collection
    {
        if (!$vendor->isVendor()) {
            throw new \InvalidArgumentException('User must be a vendor');
        }

        $vendorProfile = $vendor->vendorProfile;

        // Get all active categories with visible product counts
        return Category::active()
            ->withCount(['products' => function($q) use ($vendor, $vendorProfile) {
                $q->active()
                  ->where(function($pq) use ($vendor, $vendorProfile) {
                      $pq->whereDoesntHave('visibility')
                         ->orWhereHas('visibility', function($vq) use ($vendor) {
                             $vq->where('vendor_id', $vendor->id)
                                ->where('is_visible', true);
                         });

                      if ($vendorProfile && $vendorProfile->vendor_group_id) {
                          $pq->orWhereHas('visibility', function($vq) use ($vendorProfile) {
                              $vq->where('vendor_group_id', $vendorProfile->vendor_group_id)
                                 ->where('is_visible', true)
                                 ->whereNull('vendor_id');
                          });
                      }
                  })
                  ->whereDoesntHave('visibility', function($vq) use ($vendor) {
                      $vq->where('vendor_id', $vendor->id)
                         ->where('is_visible', false);
                  });
            }])
            ->having('products_count', '>', 0)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get product details for vendor including pricing
     */
    public function getProductDetailsForVendor(Product $product, User $vendor): array
    {
        if (!$this->isProductVisible($product, $vendor)) {
            throw new \Exception('Product not visible to vendor');
        }

        $pricingService = app(\App\Services\Pricing\PricingService::class);

        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->getName(),
                'full_path' => $product->category->getFullPath(),
            ] : null,
            'base_price' => $product->base_price,
            'stock_quantity' => $product->stock_quantity,
            'minimum_order_quantity' => $product->minimum_order_quantity,
            'order_multiple' => $product->order_multiple,
            'allow_backorder' => $product->allow_backorder,
            'images' => $product->images->map(fn($img) => [
                'id' => $img->id,
                'url' => $img->getUrl(),
                'is_primary' => $img->is_primary,
            ]),
            'pricing_tiers' => $pricingService->getPricingTiers($product, $vendor),
            'is_in_stock' => $product->isInStock(),
            'is_low_stock' => $product->isLowStock(),
        ];
    }

    /**
     * Search products across catalog with advanced filters
     */
    public function searchProducts(User $vendor, string $query, array $options = []): LengthAwarePaginator
    {
        $filters = array_merge(['search' => $query], $options);
        $perPage = $options['per_page'] ?? 15;

        return $this->getVisibleProductsForVendor($vendor, $filters)->paginate($perPage);
    }
}

<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Catalog\CatalogService;
use App\Services\Pricing\PricingService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected CatalogService $catalogService,
        protected PricingService $pricingService
    ) {}

    /**
     * Get all products visible to vendor
     */
    public function index(Request $request)
    {
        $filters = $request->only(['category_id', 'search', 'in_stock', 'sort_by', 'sort_order']);
        $perPage = $request->input('per_page', 15);

        $products = $this->catalogService
            ->getVisibleProductsForVendor($request->user(), $filters)
            ->paginate($perPage);

        // Add pricing for current vendor to each product
        $products->getCollection()->transform(function ($product) use ($request) {
            $pricing = $this->pricingService->calculatePrice($product, $request->user(), 1);

            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->getName($request->user()->locale),
                'description' => $product->getDescription($request->user()->locale),
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->getName($request->user()->locale),
                ] : null,
                'base_price' => $product->base_price,
                'your_price' => $pricing['final_price'],
                'discount' => $pricing['promotion_discount'],
                'stock_quantity' => $product->stock_quantity,
                'minimum_order_quantity' => $product->minimum_order_quantity,
                'order_multiple' => $product->order_multiple,
                'is_in_stock' => $product->isInStock(),
                'primary_image' => $product->primaryImage ? [
                    'url' => $product->primaryImage->getUrl(),
                ] : null,
            ];
        });

        return response()->json($products);
    }

    /**
     * Get single product details
     */
    public function show(Request $request, Product $product)
    {
        // Check visibility
        if (!$this->catalogService->isProductVisible($product, $request->user())) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }

        $locale = $request->user()->locale;
        $pricingTiers = $this->pricingService->getPricingTiers($product, $request->user());

        return response()->json([
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->getName($locale),
            'description' => $product->getDescription($locale),
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->getName($locale),
                'full_path' => $product->category->getFullPath($locale),
            ] : null,
            'base_price' => $product->base_price,
            'stock_quantity' => $product->stock_quantity,
            'minimum_order_quantity' => $product->minimum_order_quantity,
            'order_multiple' => $product->order_multiple,
            'allow_backorder' => $product->allow_backorder,
            'is_in_stock' => $product->isInStock(),
            'is_low_stock' => $product->isLowStock(),
            'images' => $product->images->map(fn($img) => [
                'id' => $img->id,
                'url' => $img->getUrl(),
                'is_primary' => $img->is_primary,
            ]),
            'pricing_tiers' => $pricingTiers,
        ]);
    }

    /**
     * Get product categories
     */
    public function categories(Request $request)
    {
        $categories = $this->catalogService->getCategoriesForVendor($request->user());
        $locale = $request->user()->locale;

        return response()->json(
            $categories->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->getName($locale),
                'slug' => $cat->slug,
                'products_count' => $cat->products_count,
                'parent_id' => $cat->parent_id,
            ])
        );
    }

    /**
     * Calculate price for quantity
     */
    public function calculatePrice(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // Check visibility
        if (!$this->catalogService->isProductVisible($product, $request->user())) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }

        $pricing = $this->pricingService->calculatePrice(
            $product,
            $request->user(),
            $request->quantity
        );

        return response()->json($pricing);
    }

    /**
     * Search products
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $options = $request->only(['category_id', 'in_stock', 'per_page']);
        $products = $this->catalogService->searchProducts(
            $request->user(),
            $request->q,
            $options
        );

        $locale = $request->user()->locale;

        $products->getCollection()->transform(function ($product) use ($request, $locale) {
            $pricing = $this->pricingService->calculatePrice($product, $request->user(), 1);

            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->getName($locale),
                'category_name' => $product->category?->getName($locale),
                'your_price' => $pricing['final_price'],
                'stock_quantity' => $product->stock_quantity,
                'is_in_stock' => $product->isInStock(),
                'primary_image' => $product->primaryImage ? [
                    'url' => $product->primaryImage->getUrl(),
                ] : null,
            ];
        });

        return response()->json($products);
    }
}

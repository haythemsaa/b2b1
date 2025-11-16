<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Product\ProductVariant;
use App\Models\Product\ProductBundle;
use App\Services\Catalog\CatalogService;
use App\Services\Pricing\PricingService;
use App\Services\Inventory\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function __construct(
        protected CatalogService $catalogService,
        protected PricingService $pricingService,
        protected StockService $stockService
    ) {}

    /**
     * Get all products
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'primaryImage'])
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('in_stock')) {
            $query->inStock();
        }

        if ($request->filled('low_stock')) {
            $query->lowStock();
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name_fr', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Get single product
     */
    public function show(Product $product)
    {
        $product->load(['category', 'images', 'pricing', 'visibility']);

        return response()->json($product);
    }

    /**
     * Create product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'required|string|unique:products,sku|max:50',
            'name_fr' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description_fr' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'minimum_order_quantity' => 'nullable|integer|min:1',
            'order_multiple' => 'nullable|integer|min:1',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'allow_backorder' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Produit créé avec succès',
            'product' => $product,
        ], 201);
    }

    /**
     * Update product
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku' => 'sometimes|string|unique:products,sku,' . $product->id . '|max:50',
            'name_fr' => 'sometimes|string|max:255',
            'name_ar' => 'sometimes|nullable|string|max:255',
            'description_fr' => 'sometimes|nullable|string',
            'description_ar' => 'sometimes|nullable|string',
            'category_id' => 'sometimes|nullable|exists:categories,id',
            'base_price' => 'sometimes|numeric|min:0',
            'minimum_order_quantity' => 'sometimes|integer|min:1',
            'order_multiple' => 'sometimes|integer|min:1',
            'low_stock_threshold' => 'sometimes|integer|min:0',
            'allow_backorder' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Produit mis à jour avec succès',
            'product' => $product,
        ]);
    }

    /**
     * Delete product (soft delete)
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Produit supprimé avec succès',
        ]);
    }

    /**
     * Get categories
     */
    public function categories(Request $request)
    {
        $categories = Category::active()
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        return response()->json($categories);
    }

    /**
     * Adjust stock
     */
    public function adjustStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $movement = $this->stockService->adjustStock(
                $product,
                $validated['quantity'],
                $validated['notes'] ?? 'Ajustement manuel par admin'
            );

            return response()->json([
                'message' => 'Stock ajusté avec succès',
                'product' => $product->fresh(),
                'movement' => $movement,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'ajustement du stock',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get stock history
     */
    public function stockHistory(Request $request, Product $product)
    {
        $filters = $request->only(['type', 'from_date', 'to_date']);
        $perPage = $request->input('per_page', 20);

        $history = $this->stockService
            ->getProductStockHistory($product, $filters)
            ->paginate($perPage);

        return response()->json($history);
    }

    /**
     * Set vendor pricing
     */
    public function setVendorPricing(Request $request, Product $product)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:users,id',
            'price' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'min_quantity' => 'nullable|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
        ]);

        try {
            $vendor = \App\Models\User::findOrFail($validated['vendor_id']);

            $pricing = $this->pricingService->setVendorPricing($product, $vendor, $validated);

            return response()->json([
                'message' => 'Tarification vendeur définie avec succès',
                'pricing' => $pricing,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Set group pricing
     */
    public function setGroupPricing(Request $request, Product $product)
    {
        $validated = $request->validate([
            'vendor_group_id' => 'required|exists:vendor_groups,id',
            'price' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'min_quantity' => 'nullable|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
        ]);

        try {
            $pricing = $this->pricingService->setGroupPricing(
                $product,
                $validated['vendor_group_id'],
                $validated
            );

            return response()->json([
                'message' => 'Tarification groupe définie avec succès',
                'pricing' => $pricing,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Set vendor visibility
     */
    public function setVendorVisibility(Request $request, Product $product)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:users,id',
            'is_visible' => 'required|boolean',
        ]);

        try {
            $vendor = \App\Models\User::findOrFail($validated['vendor_id']);

            $visibility = $this->catalogService->setProductVisibilityForVendor(
                $product,
                $vendor,
                $validated['is_visible']
            );

            return response()->json([
                'message' => 'Visibilité mise à jour avec succès',
                'visibility' => $visibility,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get inventory statistics
     */
    public function inventoryStats()
    {
        $stats = $this->stockService->getInventoryStats();

        return response()->json($stats);
    }

    /**
     * Get low stock products
     */
    public function lowStock(Request $request)
    {
        $threshold = $request->input('threshold');
        $products = $this->stockService->getLowStockProducts($threshold);

        return response()->json($products);
    }

    // ==========================================
    // Advanced Product System Methods
    // ==========================================

    /**
     * Create advanced product (Simple, Variable, Bundle, Configurable)
     */
    public function createAdvanced(Request $request)
    {
        $validated = $this->validateAdvancedProduct($request);

        try {
            DB::beginTransaction();

            // Create base product
            $product = Product::create([
                'vendor_id' => auth()->id(),
                'category_id' => $validated['category_id'],
                'name_fr' => $validated['name'],
                'sku' => $validated['sku'],
                'description_fr' => $validated['description'] ?? '',
                'base_price' => $validated['price'] ?? 0,
                'stock_quantity' => $validated['stock'] ?? 0,
                'minimum_order_quantity' => $validated['moq'] ?? 1,
                'is_active' => true,
                'meta_data' => [
                    'type' => $validated['type'],
                    'attributes' => $validated['attributes'] ?? [],
                    'compare_price' => $validated['compare_price'] ?? null,
                ]
            ]);

            // Handle type-specific data
            switch ($validated['type']) {
                case 'variable':
                    $this->createVariants($product, $validated['variants'] ?? []);
                    break;

                case 'bundle':
                    $this->createBundleItems($product, $validated['bundle_items'] ?? []);
                    break;

                case 'configurable':
                    $product->update([
                        'meta_data' => array_merge($product->meta_data ?? [], [
                            'custom_options' => $validated['custom_options'] ?? []
                        ])
                    ]);
                    break;
            }

            DB::commit();

            return response()->json([
                'data' => $product->load(['variants', 'bundleItems']),
                'message' => 'Product created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update advanced product
     */
    public function updateAdvanced(Request $request, Product $product)
    {
        $validated = $this->validateAdvancedProduct($request, $product);

        try {
            DB::beginTransaction();

            // Update base product
            $metaData = $product->meta_data ?? [];
            $metaData['type'] = $validated['type'] ?? ($metaData['type'] ?? 'simple');
            $metaData['attributes'] = $validated['attributes'] ?? ($metaData['attributes'] ?? []);
            $metaData['compare_price'] = $validated['compare_price'] ?? ($metaData['compare_price'] ?? null);

            $product->update([
                'category_id' => $validated['category_id'] ?? $product->category_id,
                'name_fr' => $validated['name'] ?? $product->name_fr,
                'sku' => $validated['sku'] ?? $product->sku,
                'description_fr' => $validated['description'] ?? $product->description_fr,
                'base_price' => $validated['price'] ?? $product->base_price,
                'stock_quantity' => $validated['stock'] ?? $product->stock_quantity,
                'minimum_order_quantity' => $validated['moq'] ?? $product->minimum_order_quantity,
                'meta_data' => $metaData,
            ]);

            $type = $metaData['type'];

            // Handle type-specific updates
            if ($type === 'variable' && isset($validated['variants'])) {
                $product->variants()->delete();
                $this->createVariants($product, $validated['variants']);
            }

            if ($type === 'bundle' && isset($validated['bundle_items'])) {
                $product->bundleItems()->delete();
                $this->createBundleItems($product, $validated['bundle_items']);
            }

            if ($type === 'configurable' && isset($validated['custom_options'])) {
                $metaData['custom_options'] = $validated['custom_options'];
                $product->update(['meta_data' => $metaData]);
            }

            DB::commit();

            return response()->json([
                'data' => $product->load(['variants', 'bundleItems']),
                'message' => 'Product updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate advanced product data
     */
    private function validateAdvancedProduct(Request $request, ?Product $product = null): array
    {
        $rules = [
            'type' => 'required|in:simple,variable,bundle,configurable',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:product_categories,id',
            'attributes' => 'nullable|array',
            'price' => 'nullable|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'moq' => 'nullable|integer|min:1',
        ];

        // Type-specific validation
        $type = $request->input('type');

        if ($type === 'variable') {
            $rules['variants'] = 'required|array|min:1';
            $rules['variants.*.sku'] = 'required|string';
            $rules['variants.*.name'] = 'nullable|string';
            $rules['variants.*.attributes'] = 'required|array';
            $rules['variants.*.price'] = 'required|numeric|min:0';
            $rules['variants.*.stock'] = 'required|integer|min:0';
            $rules['variants.*.moq'] = 'nullable|integer|min:1';
            $rules['variants.*.is_active'] = 'boolean';
        }

        if ($type === 'bundle') {
            $rules['bundle_items'] = 'required|array|min:1';
            $rules['bundle_items.*.product_id'] = 'required|exists:products,id';
            $rules['bundle_items.*.quantity'] = 'required|integer|min:1';
            $rules['bundle_items.*.discount_percentage'] = 'nullable|numeric|min:0|max:100';
        }

        if ($type === 'configurable') {
            $rules['custom_options'] = 'nullable|array';
            $rules['custom_options.*.name'] = 'required|string';
            $rules['custom_options.*.type'] = 'required|in:text,select,checkbox';
            $rules['custom_options.*.price_modifier'] = 'nullable|numeric';
            $rules['custom_options.*.required'] = 'boolean';
        }

        // Unique SKU validation
        if ($product) {
            $rules['sku'] .= '|unique:products,sku,' . $product->id;
        } else {
            $rules['sku'] .= '|unique:products,sku';
        }

        return $request->validate($rules);
    }

    /**
     * Create product variants
     */
    private function createVariants(Product $product, array $variants): void
    {
        foreach ($variants as $variantData) {
            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $variantData['sku'],
                'name' => $variantData['name'] ?? null,
                'attributes' => $variantData['attributes'],
                'price' => $variantData['price'],
                'compare_price' => $variantData['compare_price'] ?? null,
                'stock' => $variantData['stock'] ?? 0,
                'moq' => $variantData['moq'] ?? $product->minimum_order_quantity,
                'image' => $variantData['image'] ?? null,
                'is_active' => $variantData['is_active'] ?? true,
            ]);
        }
    }

    /**
     * Create bundle items
     */
    private function createBundleItems(Product $product, array $items): void
    {
        foreach ($items as $itemData) {
            ProductBundle::create([
                'bundle_product_id' => $product->id,
                'product_id' => $itemData['product_id'],
                'quantity' => $itemData['quantity'],
                'discount_percentage' => $itemData['discount_percentage'] ?? 0,
            ]);
        }
    }

    /**
     * Duplicate product
     */
    public function duplicate(Product $product)
    {
        try {
            DB::beginTransaction();

            $newProduct = $product->replicate();
            $newProduct->sku = $product->sku . '-copy-' . time();
            $newProduct->name_fr = $product->name_fr . ' (Copy)';
            $newProduct->save();

            $metaType = $product->meta_data['type'] ?? 'simple';

            // Duplicate variants
            if ($metaType === 'variable') {
                foreach ($product->variants as $variant) {
                    $newVariant = $variant->replicate();
                    $newVariant->product_id = $newProduct->id;
                    $newVariant->sku = $variant->sku . '-copy-' . time();
                    $newVariant->save();
                }
            }

            // Duplicate bundle items
            if ($metaType === 'bundle') {
                foreach ($product->bundleItems as $item) {
                    $newItem = $item->replicate();
                    $newItem->bundle_product_id = $newProduct->id;
                    $newItem->save();
                }
            }

            DB::commit();

            return response()->json([
                'data' => $newProduct->load(['variants', 'bundleItems']),
                'message' => 'Product duplicated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to duplicate product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update stock
     */
    public function bulkUpdateStock(Request $request)
    {
        $validated = $request->validate([
            'updates' => 'required|array',
            'updates.*.product_id' => 'required|exists:products,id',
            'updates.*.stock' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['updates'] as $update) {
                Product::where('id', $update['product_id'])
                    ->update(['stock_quantity' => $update['stock']]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Stock updated successfully for ' . count($validated['updates']) . ' products'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update stock: ' . $e->getMessage()
            ], 500);
        }
    }
}

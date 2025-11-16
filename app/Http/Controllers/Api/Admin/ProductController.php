<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
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
}

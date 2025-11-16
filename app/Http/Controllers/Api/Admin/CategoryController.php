<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ProductCategory::with('parent')
            ->orderBy('order')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'parent_id' => $category->parent_id,
                    'parent_name' => $category->parent?->name,
                    'breadcrumb' => $category->breadcrumb,
                    'depth' => count($category->path) - 1,
                    'image' => $category->image,
                    'order' => $category->order,
                    'is_active' => $category->is_active,
                    'children_count' => $category->children()->count(),
                    'products_count' => $category->products()->count(),
                ];
            });

        return response()->json([
            'data' => $categories,
            'meta' => [
                'total' => $categories->count(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:product_categories,id',
            'image' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Auto-generate slug if not provided
        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = ProductCategory::create($validated);

        return response()->json([
            'data' => $category,
            'message' => 'Category created successfully'
        ], 201);
    }

    public function show(ProductCategory $category): JsonResponse
    {
        $category->load(['parent', 'children', 'attributes']);

        return response()->json([
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'parent' => $category->parent,
                'breadcrumb' => $category->breadcrumb,
                'path' => $category->path,
                'image' => $category->image,
                'order' => $category->order,
                'is_active' => $category->is_active,
                'children' => $category->children,
                'attributes' => $category->attributes,
                'meta_data' => $category->meta_data,
            ]
        ]);
    }

    public function update(Request $request, ProductCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:product_categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:product_categories,id',
            'image' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Prevent circular reference
        if (isset($validated['parent_id']) && $validated['parent_id']) {
            $parent = ProductCategory::find($validated['parent_id']);
            $parentPath = $parent->path ?? [];

            if (collect($parentPath)->contains('id', $category->id)) {
                return response()->json([
                    'message' => 'Cannot set category as its own descendant'
                ], 422);
            }
        }

        $category->update($validated);

        return response()->json([
            'data' => $category,
            'message' => 'Category updated successfully'
        ]);
    }

    public function destroy(ProductCategory $category): JsonResponse
    {
        // Check if has children or products
        $childrenCount = $category->children()->count();
        $productsCount = $category->products()->count();

        if ($childrenCount > 0 || $productsCount > 0) {
            return response()->json([
                'message' => "Cannot delete category with {$childrenCount} subcategories and {$productsCount} products"
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }

    public function stats(): JsonResponse
    {
        $total = ProductCategory::count();
        $rootCategories = ProductCategory::whereNull('parent_id')->count();
        $activeCategories = ProductCategory::where('is_active', true)->count();

        // Calculate max depth
        $maxDepth = 0;
        ProductCategory::all()->each(function ($category) use (&$maxDepth) {
            $depth = count($category->path) - 1;
            if ($depth > $maxDepth) {
                $maxDepth = $depth;
            }
        });

        return response()->json([
            'data' => [
                'total' => $total,
                'root_categories' => $rootCategories,
                'active' => $activeCategories,
                'max_depth' => $maxDepth,
            ]
        ]);
    }

    public function attributes(ProductCategory $category): JsonResponse
    {
        $attributes = $category->attributes()
            ->withPivot('is_required', 'order')
            ->orderBy('category_attributes.order')
            ->get();

        return response()->json([
            'data' => $attributes
        ]);
    }

    public function attachAttribute(Request $request, ProductCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'attribute_id' => 'required|exists:product_attributes,id',
            'is_required' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        // Check if already attached
        if ($category->attributes()->where('attribute_id', $validated['attribute_id'])->exists()) {
            return response()->json([
                'message' => 'Attribute already assigned to this category'
            ], 422);
        }

        $category->attributes()->attach($validated['attribute_id'], [
            'is_required' => $validated['is_required'] ?? false,
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'message' => 'Attribute assigned successfully'
        ], 201);
    }

    public function updateAttribute(Request $request, ProductCategory $category, int $attributeId): JsonResponse
    {
        $validated = $request->validate([
            'is_required' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        $category->attributes()->updateExistingPivot($attributeId, $validated);

        return response()->json([
            'message' => 'Attribute updated successfully'
        ]);
    }

    public function detachAttribute(ProductCategory $category, int $attributeId): JsonResponse
    {
        $category->attributes()->detach($attributeId);

        return response()->json([
            'message' => 'Attribute removed successfully'
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product\ProductAttribute;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    public function index(): JsonResponse
    {
        $attributes = ProductAttribute::orderBy('order')->get()->map(function ($attribute) {
            return [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'slug' => $attribute->slug,
                'type' => $attribute->type,
                'type_label' => ProductAttribute::types()[$attribute->type] ?? $attribute->type,
                'options' => $attribute->options,
                'is_filterable' => $attribute->is_filterable,
                'is_required' => $attribute->is_required,
                'is_variant' => $attribute->is_variant,
                'order' => $attribute->order,
                'categories_count' => $attribute->categories()->count(),
            ];
        });

        return response()->json([
            'data' => $attributes,
            'meta' => [
                'total' => $attributes->count(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_attributes,slug',
            'type' => 'required|in:text,number,select,multiselect,color,boolean',
            'options' => 'nullable|array',
            'options.*' => 'string',
            'is_filterable' => 'boolean',
            'is_required' => 'boolean',
            'is_variant' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        // Auto-generate slug if not provided
        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Validate that select/multiselect types have options
        if (in_array($validated['type'], ['select', 'multiselect'])) {
            if (empty($validated['options'])) {
                return response()->json([
                    'message' => 'Select and Multiselect attributes must have options'
                ], 422);
            }
        }

        $attribute = ProductAttribute::create($validated);

        return response()->json([
            'data' => $attribute,
            'message' => 'Attribute created successfully'
        ], 201);
    }

    public function show(ProductAttribute $attribute): JsonResponse
    {
        $attribute->load('categories');

        return response()->json([
            'data' => [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'slug' => $attribute->slug,
                'type' => $attribute->type,
                'type_label' => ProductAttribute::types()[$attribute->type] ?? $attribute->type,
                'options' => $attribute->options,
                'is_filterable' => $attribute->is_filterable,
                'is_required' => $attribute->is_required,
                'is_variant' => $attribute->is_variant,
                'order' => $attribute->order,
                'categories' => $attribute->categories,
            ]
        ]);
    }

    public function update(Request $request, ProductAttribute $attribute): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:product_attributes,slug,' . $attribute->id,
            'type' => 'sometimes|required|in:text,number,select,multiselect,color,boolean',
            'options' => 'nullable|array',
            'options.*' => 'string',
            'is_filterable' => 'boolean',
            'is_required' => 'boolean',
            'is_variant' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        // Validate that select/multiselect types have options
        $type = $validated['type'] ?? $attribute->type;
        if (in_array($type, ['select', 'multiselect'])) {
            $options = $validated['options'] ?? $attribute->options;
            if (empty($options)) {
                return response()->json([
                    'message' => 'Select and Multiselect attributes must have options'
                ], 422);
            }
        }

        $attribute->update($validated);

        return response()->json([
            'data' => $attribute,
            'message' => 'Attribute updated successfully'
        ]);
    }

    public function destroy(ProductAttribute $attribute): JsonResponse
    {
        // Check if used by categories
        $categoriesCount = $attribute->categories()->count();

        if ($categoriesCount > 0) {
            return response()->json([
                'message' => "Cannot delete attribute used by {$categoriesCount} categories"
            ], 422);
        }

        $attribute->delete();

        return response()->json([
            'message' => 'Attribute deleted successfully'
        ]);
    }

    public function stats(): JsonResponse
    {
        $total = ProductAttribute::count();
        $filterable = ProductAttribute::where('is_filterable', true)->count();
        $variant = ProductAttribute::where('is_variant', true)->count();
        $required = ProductAttribute::where('is_required', true)->count();

        // Count attributes by type
        $byType = ProductAttribute::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        return response()->json([
            'data' => [
                'total' => $total,
                'filterable' => $filterable,
                'variant' => $variant,
                'required' => $required,
                'by_type' => $byType,
            ]
        ]);
    }

    public function validateValue(Request $request, ProductAttribute $attribute): JsonResponse
    {
        $request->validate([
            'value' => 'required',
        ]);

        $value = $request->input('value');
        $isValid = $attribute->validateValue($value);

        return response()->json([
            'valid' => $isValid,
            'message' => $isValid ? 'Value is valid' : 'Invalid value for this attribute type'
        ]);
    }
}

@extends('layouts.app')

@section('title', 'Product Details')

@section('sidebar')
    @include('vendor.partials.sidebar')
@endsection

@section('content')
<div x-data="productDetailData({{ $id }})" x-init="init()">
    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-20">
        <svg class="animate-spin h-12 w-12 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    <!-- Product Content -->
    <div x-show="!loading && product" class="max-w-7xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="mb-6 text-sm">
            <ol class="flex items-center space-x-2 text-gray-600">
                <li><a href="/vendor/products" class="hover:text-blue-600">Products</a></li>
                <li><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg></li>
                <li class="text-gray-900 font-medium" x-text="product?.name"></li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Product Image & Info -->
            <div class="lg:col-span-2">
                <!-- Product Image -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="bg-gray-100 rounded-t-lg p-12 flex items-center justify-center" style="min-height: 400px;">
                        <template x-if="currentImage">
                            <img :src="currentImage" :alt="product?.name" class="max-h-96 object-contain">
                        </template>
                        <template x-if="!currentImage">
                            <svg class="w-48 h-48 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                            </svg>
                        </template>
                    </div>

                    <!-- Image Thumbnails -->
                    <div x-show="product?.images?.length > 1" class="p-4 flex gap-2 overflow-x-auto">
                        <template x-for="(image, index) in product?.images" :key="index">
                            <div @click="currentImage = image.url"
                                 :class="currentImage === image.url ? 'border-blue-500 border-2' : 'border-gray-200'"
                                 class="flex-shrink-0 w-20 h-20 border-2 rounded cursor-pointer hover:border-blue-300 overflow-hidden">
                                <img :src="image.url" class="w-full h-full object-cover">
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-sm font-semibold rounded-full" x-text="product?.category?.name"></span>
                            <span x-show="product?.type && product.type !== 'simple'"
                                  class="inline-block px-3 py-1 bg-purple-100 text-purple-800 text-sm font-semibold rounded-full"
                                  x-text="product?.type"></span>
                        </div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2" x-text="product?.name"></h1>
                        <p class="text-gray-600 text-lg" x-text="product?.description"></p>
                    </div>

                    <!-- Specs Grid -->
                    <div class="grid grid-cols-2 gap-4 py-6 border-t border-b">
                        <div>
                            <p class="text-sm text-gray-600">SKU</p>
                            <p class="text-lg font-semibold text-gray-900" x-text="getActiveSKU()"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Category</p>
                            <p class="text-lg font-semibold text-gray-900" x-text="product?.category?.name"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Minimum Order Quantity</p>
                            <p class="text-lg font-semibold text-gray-900" x-text="getMOQ() + ' units'"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Available Stock</p>
                            <p class="text-lg font-semibold text-gray-900" x-text="getActiveStock() + ' units'"></p>
                        </div>
                    </div>

                    <!-- Product Attributes -->
                    <div x-show="product?.attributes && Object.keys(product.attributes).length > 0" class="mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Specifications</h3>
                        <dl class="grid grid-cols-2 gap-3">
                            <template x-for="(value, key) in product?.attributes" :key="key">
                                <div>
                                    <dt class="text-sm text-gray-600 capitalize" x-text="key.replace('_', ' ').replace('-', ' ')"></dt>
                                    <dd class="text-sm font-medium text-gray-900" x-text="value"></dd>
                                </div>
                            </template>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Right Column - Order Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-6">
                    <!-- Type Badge -->
                    <div x-show="product?.type && product.type !== 'simple'" class="mb-4">
                        <span class="inline-block px-3 py-1 bg-purple-100 text-purple-800 text-sm font-semibold rounded-full uppercase" x-text="product?.type + ' Product'"></span>
                    </div>

                    <!-- Price -->
                    <div class="mb-6">
                        <p class="text-sm text-gray-600 mb-1">Price per unit</p>
                        <template x-if="product?.compare_price && product.compare_price > getActivePrice()">
                            <div>
                                <p class="text-4xl font-bold text-gray-900 mb-1" x-text="formatCurrency(getActivePrice())"></p>
                                <div class="flex items-center gap-2">
                                    <span class="text-lg text-gray-500 line-through" x-text="formatCurrency(product.compare_price)"></span>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm font-medium">
                                        Save <span x-text="Math.round(((product.compare_price - getActivePrice()) / product.compare_price) * 100)"></span>%
                                    </span>
                                </div>
                            </div>
                        </template>
                        <template x-if="!product?.compare_price || product.compare_price <= getActivePrice()">
                            <p class="text-4xl font-bold text-gray-900 mb-2" x-text="formatCurrency(getActivePrice())"></p>
                        </template>
                        <p class="text-sm text-gray-600">Minimum order: <span class="font-semibold" x-text="getMOQ() + ' units'"></span></p>
                    </div>

                    <!-- Variable Product: Variant Selector -->
                    <div x-show="product?.type === 'variable' && variants.length > 0" x-cloak class="mb-6 pb-6 border-b">
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Select Options</h3>

                        <!-- Variant Attributes -->
                        <div class="space-y-4">
                            <template x-for="(options, attrName) in variantAttributeOptions" :key="attrName">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2 capitalize" x-text="attrName"></label>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="option in options" :key="option">
                                            <button @click="selectVariantAttribute(attrName, option)"
                                                    :class="selectedVariantAttributes[attrName] === option
                                                        ? 'bg-blue-600 text-white border-blue-600'
                                                        : 'bg-white text-gray-700 border-gray-300 hover:border-blue-600'"
                                                    class="px-4 py-2 border-2 rounded-lg font-medium transition-colors text-sm"
                                                    x-text="option"></button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Selected Variant Info -->
                        <div x-show="selectedVariant" class="mt-4 p-3 bg-blue-50 rounded-lg">
                            <p class="text-sm font-medium text-gray-900" x-text="selectedVariant?.display_name || selectedVariant?.name"></p>
                            <p class="text-xs text-gray-600 mt-1">SKU: <span x-text="selectedVariant?.sku"></span></p>
                        </div>
                    </div>

                    <!-- Bundle Product: Bundle Items -->
                    <div x-show="product?.type === 'bundle' && bundleItems.length > 0" x-cloak class="mb-6 pb-6 border-b">
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Bundle Includes</h3>
                        <div class="space-y-2">
                            <template x-for="item in bundleItems" :key="item.id">
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900" x-text="item.product_name"></p>
                                        <p class="text-xs text-gray-600">Qty: <span x-text="item.quantity"></span></p>
                                    </div>
                                    <div class="text-right">
                                        <template x-if="item.discount_percentage > 0">
                                            <div>
                                                <p class="text-xs text-gray-500 line-through" x-text="formatCurrency(item.unit_price * item.quantity)"></p>
                                                <p class="text-sm font-semibold text-green-600" x-text="formatCurrency(item.discounted_price)"></p>
                                            </div>
                                        </template>
                                        <template x-if="!item.discount_percentage || item.discount_percentage === 0">
                                            <p class="text-sm font-semibold text-gray-900" x-text="formatCurrency(item.unit_price * item.quantity)"></p>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="mt-3 p-3 bg-green-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-900">Bundle Price:</span>
                                <span class="text-xl font-bold text-green-600" x-text="formatCurrency(bundleTotalPrice)"></span>
                            </div>
                            <p class="text-xs text-green-700 mt-1">
                                You save <span x-text="formatCurrency(bundleTotalSavings)"></span>!
                            </p>
                        </div>
                    </div>

                    <!-- Configurable Product: Custom Options -->
                    <div x-show="product?.type === 'configurable' && customOptions.length > 0" x-cloak class="mb-6 pb-6 border-b">
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Customize</h3>
                        <div class="space-y-3">
                            <template x-for="option in customOptions" :key="option.name">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <span x-text="option.name"></span>
                                        <span x-show="option.required" class="text-red-500">*</span>
                                        <span x-show="option.price_modifier" class="text-xs text-gray-500">
                                            (+<span x-text="formatCurrency(option.price_modifier)"></span>)
                                        </span>
                                    </label>

                                    <!-- Text Input -->
                                    <template x-if="option.type === 'text'">
                                        <input type="text" x-model="selectedCustomOptions[option.name]"
                                               @input="recalculateConfigurablePrice()"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                               :placeholder="'Enter ' + option.name">
                                    </template>

                                    <!-- Select -->
                                    <template x-if="option.type === 'select'">
                                        <select x-model="selectedCustomOptions[option.name]"
                                                @change="recalculateConfigurablePrice()"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                            <option value="">Select...</option>
                                            <template x-for="val in option.values?.split('\n')" :key="val">
                                                <option :value="val" x-text="val"></option>
                                            </template>
                                        </select>
                                    </template>

                                    <!-- Checkbox -->
                                    <template x-if="option.type === 'checkbox'">
                                        <label class="flex items-center">
                                            <input type="checkbox" x-model="selectedCustomOptions[option.name]"
                                                   @change="recalculateConfigurablePrice()"
                                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                            <span class="ml-2 text-sm text-gray-700">Yes</span>
                                        </label>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <div x-show="configurablePrice > (product?.base_price || 0)" class="mt-3 p-3 bg-blue-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900">Total with Options:</span>
                                <span class="text-xl font-bold text-blue-600" x-text="formatCurrency(configurablePrice)"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Status -->
                    <div class="mb-6 p-4 rounded-lg" :class="getStockBgClass(getActiveStock())">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" :class="getStockTextClass(getActiveStock())" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-semibold" :class="getStockTextClass(getActiveStock())">
                                <span x-show="getActiveStock() > 20">In Stock</span>
                                <span x-show="getActiveStock() > 0 && getActiveStock() <= 20">Low Stock</span>
                                <span x-show="getActiveStock() === 0">Out of Stock</span>
                            </span>
                        </div>
                        <p class="text-sm mt-1" :class="getStockTextClass(getActiveStock())" x-text="getActiveStock() + ' units available'"></p>
                    </div>

                    <!-- Quantity Selector -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                        <div class="flex items-center">
                            <button
                                @click="quantity = Math.max(getMOQ(), quantity - getMOQ())"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-l-lg hover:bg-gray-300"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                            </button>
                            <input
                                type="number"
                                x-model.number="quantity"
                                :min="getMOQ()"
                                :step="getMOQ()"
                                class="w-full px-4 py-2 text-center border-t border-b border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                            <button
                                @click="quantity += getMOQ()"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-r-lg hover:bg-gray-300"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="text-lg font-semibold" x-text="formatCurrency(getTotalPrice())"></span>
                        </div>
                        <div class="flex justify-between items-center text-sm text-gray-600">
                            <span x-text="quantity + ' units × ' + formatCurrency(getActivePrice())"></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-3">
                        <button
                            @click="addToCart()"
                            :disabled="!canAddToCart()"
                            class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <span x-show="!addingToCart">Add to Cart</span>
                            <span x-show="addingToCart">Adding...</span>
                        </button>
                        <button
                            @click="createRFQ()"
                            class="w-full px-6 py-3 bg-white border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:border-gray-400 transition-colors"
                        >
                            Request Quote
                        </button>
                    </div>

                    <!-- Additional Info -->
                    <div class="mt-6 pt-6 border-t space-y-3">
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Free shipping on orders over 1000 MAD
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Delivery in 3-5 business days
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            30-day return policy
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div x-show="showSuccess" x-cloak
         class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-2 z-50">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>Added to cart successfully!</span>
    </div>
</div>
@endsection

@push('scripts')
<script>
function productDetailData(productId) {
    return {
        product: null,
        loading: true,
        quantity: 1,
        currentImage: null,
        addingToCart: false,
        showSuccess: false,

        // Variable product
        variants: [],
        selectedVariant: null,
        selectedVariantAttributes: {},

        // Bundle product
        bundleItems: [],

        // Configurable product
        customOptions: [],
        selectedCustomOptions: {},
        configurablePrice: 0,

        async init() {
            await this.loadProduct();
            this.quantity = this.getMOQ();
            this.loading = false;
        },

        async loadProduct() {
            try {
                const response = await fetch(`/api/vendor/products/${productId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + localStorage.getItem('token')
                    }
                });

                if (!response.ok) throw new Error('Failed to load product');

                this.product = await response.json();
                this.currentImage = this.product.images?.[0]?.url;

                // Load type-specific data
                if (this.product.type === 'variable') {
                    this.variants = this.product.variants || [];
                } else if (this.product.type === 'bundle') {
                    this.bundleItems = this.product.bundle_items || [];
                } else if (this.product.type === 'configurable') {
                    this.customOptions = this.product.custom_options || [];
                    this.configurablePrice = this.product.base_price;
                }
            } catch (error) {
                console.error('Error loading product:', error);
                alert('Failed to load product details');
            }
        },

        // Variant handling
        get variantAttributeOptions() {
            const options = {};
            this.variants.forEach(variant => {
                Object.entries(variant.attributes).forEach(([key, value]) => {
                    if (!options[key]) options[key] = [];
                    if (!options[key].includes(value)) {
                        options[key].push(value);
                    }
                });
            });
            return options;
        },

        selectVariantAttribute(attrName, value) {
            this.selectedVariantAttributes[attrName] = value;
            this.findMatchingVariant();
        },

        findMatchingVariant() {
            const selected = this.selectedVariantAttributes;
            const keys = Object.keys(selected);

            if (keys.length === 0) {
                this.selectedVariant = null;
                return;
            }

            this.selectedVariant = this.variants.find(variant => {
                return keys.every(key => variant.attributes[key] === selected[key]);
            });

            if (this.selectedVariant?.image) {
                this.currentImage = this.selectedVariant.image;
            }
        },

        // Bundle handling
        get bundleTotalPrice() {
            return this.bundleItems.reduce((total, item) => {
                const basePrice = item.unit_price * item.quantity;
                const discount = basePrice * (item.discount_percentage / 100);
                return total + (basePrice - discount);
            }, 0);
        },

        get bundleTotalSavings() {
            const regular = this.bundleItems.reduce((total, item) => {
                return total + (item.unit_price * item.quantity);
            }, 0);
            return regular - this.bundleTotalPrice;
        },

        // Configurable handling
        recalculateConfigurablePrice() {
            let price = this.product.base_price;
            this.customOptions.forEach(option => {
                const selected = this.selectedCustomOptions[option.name];
                if (selected && option.price_modifier) {
                    price += parseFloat(option.price_modifier);
                }
            });
            this.configurablePrice = price;
        },

        // Helper methods
        getActivePrice() {
            if (this.product.type === 'variable' && this.selectedVariant) {
                return this.selectedVariant.price;
            }
            if (this.product.type === 'bundle') {
                return this.bundleTotalPrice;
            }
            if (this.product.type === 'configurable') {
                return this.configurablePrice;
            }
            return this.product?.base_price || this.product?.your_price || 0;
        },

        getActiveStock() {
            if (this.product.type === 'variable' && this.selectedVariant) {
                return this.selectedVariant.stock;
            }
            return this.product?.stock_quantity || 0;
        },

        getMOQ() {
            if (this.product.type === 'variable' && this.selectedVariant) {
                return this.selectedVariant.moq || 1;
            }
            return this.product?.minimum_order_quantity || 1;
        },

        getActiveSKU() {
            if (this.product.type === 'variable' && this.selectedVariant) {
                return this.selectedVariant.sku;
            }
            return this.product?.sku;
        },

        getTotalPrice() {
            return this.getActivePrice() * this.quantity;
        },

        canAddToCart() {
            if (this.product.type === 'variable' && !this.selectedVariant) {
                return false;
            }
            if (this.getActiveStock() === 0) {
                return false;
            }
            if (this.quantity < this.getMOQ()) {
                return false;
            }
            return true;
        },

        async addToCart() {
            if (!this.canAddToCart()) return;

            this.addingToCart = true;

            try {
                const cartItem = {
                    product_id: this.product.id,
                    quantity: this.quantity,
                    variant_id: this.selectedVariant?.id || null,
                    custom_options: this.product.type === 'configurable' ? this.selectedCustomOptions : null,
                };

                const response = await fetch('/api/vendor/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + localStorage.getItem('token')
                    },
                    body: JSON.stringify(cartItem)
                });

                if (!response.ok) throw new Error('Failed to add to cart');

                this.showSuccess = true;
                setTimeout(() => {
                    this.showSuccess = false;
                }, 3000);

            } catch (error) {
                console.error('Error adding to cart:', error);
                alert('Failed to add to cart');
            } finally {
                this.addingToCart = false;
            }
        },

        createRFQ() {
            window.location.href = '/vendor/rfqs/create?product=' + this.product.id;
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'MAD',
                minimumFractionDigits: 2
            }).format(amount);
        },

        getStockBgClass(stock) {
            if (stock > 20) return 'bg-green-100';
            if (stock > 0) return 'bg-yellow-100';
            return 'bg-red-100';
        },

        getStockTextClass(stock) {
            if (stock > 20) return 'text-green-800';
            if (stock > 0) return 'text-yellow-800';
            return 'text-red-800';
        }
    };
}
</script>
@endpush

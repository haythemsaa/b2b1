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
                <div class="bg-gray-100 rounded-lg p-12 mb-6 flex items-center justify-center" style="min-height: 400px;">
                    <svg class="w-48 h-48 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                    </svg>
                </div>

                <!-- Product Details -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="mb-6">
                        <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-sm font-semibold rounded-full mb-3" x-text="product?.category"></span>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2" x-text="product?.name"></h1>
                        <p class="text-gray-600 text-lg" x-text="product?.description"></p>
                    </div>

                    <!-- Specs Grid -->
                    <div class="grid grid-cols-2 gap-4 py-6 border-t border-b">
                        <div>
                            <p class="text-sm text-gray-600">SKU</p>
                            <p class="text-lg font-semibold text-gray-900" x-text="product?.sku"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Category</p>
                            <p class="text-lg font-semibold text-gray-900" x-text="product?.category"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Minimum Order Quantity</p>
                            <p class="text-lg font-semibold text-gray-900" x-text="product?.moq + ' units'"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Weight</p>
                            <p class="text-lg font-semibold text-gray-900" x-text="(product?.weight || 0) + ' kg'"></p>
                        </div>
                    </div>

                    <!-- Frequently Bought Together -->
                    <div x-show="relatedProducts.length > 0" class="mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Frequently Bought Together</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <template x-for="related in relatedProducts.slice(0, 3)" :key="related.id">
                                <a :href="`/vendor/products/${related.id}`" class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="bg-gray-100 rounded p-4 mb-3 flex items-center justify-center" style="height: 100px;">
                                        <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900 mb-1" x-text="related.name"></p>
                                    <p class="text-sm font-bold text-blue-600" x-text="formatCurrency(related.price)"></p>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Order Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-6">
                    <!-- Price -->
                    <div class="mb-6">
                        <p class="text-sm text-gray-600 mb-1">Price per unit</p>
                        <p class="text-4xl font-bold text-gray-900 mb-2" x-text="formatCurrency(product?.price)"></p>
                        <p class="text-sm text-gray-600">Minimum order: <span class="font-semibold" x-text="product?.moq + ' units'"></span></p>
                    </div>

                    <!-- Stock Status -->
                    <div class="mb-6 p-4 rounded-lg" :class="getStockBgClass(product?.stock)">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" :class="getStockTextClass(product?.stock)" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-semibold" :class="getStockTextClass(product?.stock)">
                                <span x-show="product?.stock > 20">In Stock</span>
                                <span x-show="product?.stock > 0 && product?.stock <= 20">Low Stock</span>
                                <span x-show="product?.stock === 0">Out of Stock</span>
                            </span>
                        </div>
                        <p class="text-sm mt-1" :class="getStockTextClass(product?.stock)" x-text="product?.stock + ' units available'"></p>
                    </div>

                    <!-- Quantity Selector -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                        <div class="flex items-center">
                            <button
                                @click="quantity = Math.max(product?.moq || 1, quantity - (product?.moq || 1))"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-l-lg hover:bg-gray-300"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                            </button>
                            <input
                                type="number"
                                x-model="quantity"
                                :min="product?.moq || 1"
                                :step="product?.moq || 1"
                                class="w-full px-4 py-2 text-center border-t border-b border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                            <button
                                @click="quantity += (product?.moq || 1)"
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
                            <span class="text-lg font-semibold" x-text="formatCurrency((product?.price || 0) * quantity)"></span>
                        </div>
                        <div class="flex justify-between items-center text-sm text-gray-600">
                            <span x-text="quantity + ' units × ' + formatCurrency(product?.price || 0)"></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-3">
                        <button
                            @click="addToCart()"
                            :disabled="product?.stock === 0"
                            class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Add to Cart
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
</div>
@endsection

@push('scripts')
<script>
function productDetailData(productId) {
    return {
        product: null,
        relatedProducts: [],
        loading: true,
        quantity: 1,

        async init() {
            await this.loadProduct();
            await this.loadRelatedProducts();
        },

        async loadProduct() {
            this.loading = true;
            try {
                const data = await api.getProduct(productId);
                this.product = data;
                this.quantity = this.product.moq || 1;
            } catch (error) {
                console.error('Failed to load product:', error);
                alert('Failed to load product details');
            } finally {
                this.loading = false;
            }
        },

        async loadRelatedProducts() {
            try {
                const data = await api.getProductRecommendations(productId);
                this.relatedProducts = data.data || [];
            } catch (error) {
                console.error('Failed to load related products:', error);
            }
        },

        addToCart() {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');

            const existingIndex = cart.findIndex(item => item.product_id === this.product.id);

            if (existingIndex >= 0) {
                cart[existingIndex].quantity += this.quantity;
            } else {
                cart.push({
                    product_id: this.product.id,
                    name: this.product.name,
                    price: this.product.price,
                    quantity: this.quantity,
                    moq: this.product.moq
                });
            }

            localStorage.setItem('cart', JSON.stringify(cart));

            alert(`${this.quantity} units of ${this.product.name} added to cart!`);
        },

        createRFQ() {
            window.location.href = '/vendor/rfqs';
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'MAD'
            }).format(amount);
        },

        getStockBgClass(stock) {
            if (stock === 0) return 'bg-red-50';
            if (stock <= 20) return 'bg-orange-50';
            return 'bg-green-50';
        },

        getStockTextClass(stock) {
            if (stock === 0) return 'text-red-700';
            if (stock <= 20) return 'text-orange-700';
            return 'text-green-700';
        }
    };
}
</script>
@endpush

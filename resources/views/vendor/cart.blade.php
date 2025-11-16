@extends('layouts.app')

@section('title', 'Shopping Cart')

@section('sidebar')
    @include('vendor.partials.sidebar')
@endsection

@section('content')
<div x-data="cartData()" x-init="init()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Shopping Cart</h1>
        <p class="text-gray-600 mt-1">Review and checkout your items</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Cart Items -->
        <div class="lg:col-span-2">
            <!-- Cart Items -->
            <div x-show="cartItems.length > 0" class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Cart Items (<span x-text="cartItems.length"></span>)</h2>
                        <button @click="clearCart()" class="text-sm text-red-600 hover:underline">Clear Cart</button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(item, index) in cartItems" :key="index">
                            <div class="border rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start flex-1">
                                        <!-- Product Image Placeholder -->
                                        <div class="h-20 w-20 bg-gray-100 rounded flex items-center justify-center mr-4 flex-shrink-0">
                                            <svg class="w-10 h-10 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/>
                                            </svg>
                                        </div>

                                        <!-- Product Info -->
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900 mb-1" x-text="item.name"></h3>
                                            <p class="text-sm text-gray-600 mb-2">SKU: <span x-text="item.sku || 'N/A'"></span></p>
                                            <p class="text-lg font-bold text-blue-600" x-text="formatCurrency(item.price)"></p>
                                            <p class="text-xs text-gray-500" x-show="item.moq">Minimum Order: <span x-text="item.moq + ' units'"></span></p>
                                        </div>
                                    </div>

                                    <!-- Quantity Controls -->
                                    <div class="ml-4">
                                        <div class="flex items-center mb-2">
                                            <button
                                                @click="decreaseQuantity(index)"
                                                class="px-3 py-1 bg-gray-200 text-gray-700 rounded-l hover:bg-gray-300"
                                            >
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                            <input
                                                type="number"
                                                x-model="item.quantity"
                                                @change="updateQuantity(index)"
                                                :min="item.moq || 1"
                                                :step="item.moq || 1"
                                                class="w-20 px-2 py-1 text-center border-t border-b border-gray-300 focus:outline-none"
                                            >
                                            <button
                                                @click="increaseQuantity(index)"
                                                class="px-3 py-1 bg-gray-200 text-gray-700 rounded-r hover:bg-gray-300"
                                            >
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <p class="text-sm text-gray-600 text-center mb-2">
                                            Subtotal: <strong x-text="formatCurrency(item.price * item.quantity)"></strong>
                                        </p>
                                        <button
                                            @click="removeItem(index)"
                                            class="w-full text-sm text-red-600 hover:underline"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Empty Cart -->
            <div x-show="cartItems.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
                <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Your cart is empty</h3>
                <p class="text-gray-600 mb-6">Add products to your cart to get started</p>
                <a href="/vendor/products" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Browse Products
                </a>
            </div>

            <!-- Continue Shopping -->
            <div x-show="cartItems.length > 0" class="mt-4">
                <a href="/vendor/products" class="text-blue-600 hover:underline inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                    </svg>
                    Continue Shopping
                </a>
            </div>
        </div>

        <!-- Right Column - Order Summary -->
        <div class="lg:col-span-1">
            <div x-show="cartItems.length > 0" class="bg-white rounded-lg shadow p-6 sticky top-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Order Summary</h2>

                <!-- Summary Details -->
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal (<span x-text="getTotalItems()"></span> items)</span>
                        <span class="font-semibold" x-text="formatCurrency(getSubtotal())"></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Tax (20%)</span>
                        <span class="font-semibold" x-text="formatCurrency(getTax())"></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Shipping</span>
                        <span class="font-semibold" x-text="getSubtotal() >= 1000 ? 'FREE' : formatCurrency(50)"></span>
                    </div>
                    <div class="border-t pt-3 flex justify-between">
                        <span class="text-lg font-bold text-gray-900">Total</span>
                        <span class="text-lg font-bold text-gray-900" x-text="formatCurrency(getTotal())"></span>
                    </div>
                </div>

                <!-- Free Shipping Notice -->
                <div x-show="getSubtotal() < 1000" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">
                        Add <strong x-text="formatCurrency(1000 - getSubtotal())"></strong> more to get FREE shipping!
                    </p>
                </div>

                <!-- Checkout Button -->
                <button
                    @click="proceedToCheckout()"
                    class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors mb-3"
                >
                    Proceed to Checkout
                </button>

                <!-- Request Quote -->
                <button
                    @click="requestQuote()"
                    class="w-full px-6 py-3 bg-white border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:border-gray-400 transition-colors"
                >
                    Request Quote Instead
                </button>

                <!-- Benefits -->
                <div class="mt-6 pt-6 border-t space-y-3">
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Free shipping over 1000 MAD
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Secure checkout
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        30-day return policy
                    </div>
                </div>
            </div>

            <!-- Promo Code (Additional Feature) -->
            <div x-show="cartItems.length > 0" class="bg-white rounded-lg shadow p-6 mt-6">
                <h3 class="font-semibold text-gray-900 mb-3">Have a promo code?</h3>
                <div class="flex gap-2">
                    <input
                        type="text"
                        x-model="promoCode"
                        placeholder="Enter code"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    >
                    <button
                        @click="applyPromoCode()"
                        class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900"
                    >
                        Apply
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function cartData() {
    return {
        cartItems: [],
        promoCode: '',

        init() {
            this.loadCart();
        },

        loadCart() {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            this.cartItems = cart;
        },

        saveCart() {
            localStorage.setItem('cart', JSON.stringify(this.cartItems));
        },

        increaseQuantity(index) {
            const item = this.cartItems[index];
            const step = item.moq || 1;
            item.quantity += step;
            this.saveCart();
        },

        decreaseQuantity(index) {
            const item = this.cartItems[index];
            const step = item.moq || 1;
            const minQty = item.moq || 1;

            if (item.quantity - step >= minQty) {
                item.quantity -= step;
                this.saveCart();
            }
        },

        updateQuantity(index) {
            const item = this.cartItems[index];
            const minQty = item.moq || 1;

            if (item.quantity < minQty) {
                item.quantity = minQty;
            }

            this.saveCart();
        },

        removeItem(index) {
            if (confirm('Remove this item from cart?')) {
                this.cartItems.splice(index, 1);
                this.saveCart();
            }
        },

        clearCart() {
            if (confirm('Clear all items from cart?')) {
                this.cartItems = [];
                localStorage.removeItem('cart');
            }
        },

        getSubtotal() {
            return this.cartItems.reduce((total, item) => {
                return total + (item.price * item.quantity);
            }, 0);
        },

        getTax() {
            return this.getSubtotal() * 0.20; // 20% tax
        },

        getShipping() {
            return this.getSubtotal() >= 1000 ? 0 : 50;
        },

        getTotal() {
            return this.getSubtotal() + this.getTax() + this.getShipping();
        },

        getTotalItems() {
            return this.cartItems.reduce((total, item) => total + item.quantity, 0);
        },

        async proceedToCheckout() {
            if (this.cartItems.length === 0) {
                alert('Your cart is empty!');
                return;
            }

            try {
                // Create order from cart
                const orderData = {
                    items: this.cartItems.map(item => ({
                        product_id: item.product_id,
                        quantity: item.quantity,
                        price: item.price
                    })),
                    subtotal: this.getSubtotal(),
                    tax: this.getTax(),
                    shipping_cost: this.getShipping(),
                    total: this.getTotal()
                };

                const response = await api.createOrder(orderData);

                // Clear cart
                this.cartItems = [];
                localStorage.removeItem('cart');

                alert('Order placed successfully!');
                window.location.href = `/vendor/orders/${response.id}`;
            } catch (error) {
                console.error('Failed to create order:', error);
                alert('Failed to place order. Please try again.');
            }
        },

        async requestQuote() {
            if (this.cartItems.length === 0) {
                alert('Your cart is empty!');
                return;
            }

            // Convert cart to RFQ items
            const rfqData = {
                title: 'Quote Request from Cart',
                description: 'Requesting quote for cart items',
                items: this.cartItems.map(item => ({
                    product_id: item.product_id,
                    quantity: item.quantity,
                    description: item.name
                }))
            };

            try {
                const response = await api.createRfq(rfqData);

                // Clear cart
                this.cartItems = [];
                localStorage.removeItem('cart');

                alert('Quote request created successfully!');
                window.location.href = `/vendor/rfqs/${response.id}`;
            } catch (error) {
                console.error('Failed to create RFQ:', error);
                alert('Failed to create quote request. Please try again.');
            }
        },

        applyPromoCode() {
            if (!this.promoCode) {
                alert('Please enter a promo code');
                return;
            }

            // In a real implementation, this would validate the promo code with the API
            alert(`Promo code "${this.promoCode}" applied! (Demo - not actually applied)`);
            this.promoCode = '';
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'MAD'
            }).format(amount || 0);
        }
    };
}
</script>
@endpush

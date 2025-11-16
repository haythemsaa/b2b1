@extends('layouts.app')

@section('title', 'Order Details')

@section('sidebar')
    @include('vendor.partials.sidebar')
@endsection

@section('content')
<div x-data="orderDetailData({{ $id }})" x-init="init()">
    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-20">
        <svg class="animate-spin h-12 w-12 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    <!-- Order Content -->
    <div x-show="!loading && order" class="max-w-7xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="mb-6 text-sm">
            <ol class="flex items-center space-x-2 text-gray-600">
                <li><a href="/vendor/orders" class="hover:text-blue-600">Orders</a></li>
                <li><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg></li>
                <li class="text-gray-900 font-medium">Order #<span x-text="order?.id"></span></li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Order #<span x-text="order?.id"></span></h1>
                    <p class="text-gray-600">Placed on <span x-text="formatDate(order?.created_at)"></span></p>
                </div>
                <div class="mt-4 md:mt-0">
                    <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold" :class="getStatusClass(order?.status)" x-text="order?.status?.toUpperCase()"></span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex flex-wrap gap-3">
                <button
                    x-show="order?.status === 'pending' || order?.status === 'confirmed'"
                    @click="cancelOrder()"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                >
                    Cancel Order
                </button>
                <button
                    @click="reorder()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Reorder
                </button>
                <button
                    @click="downloadInvoice()"
                    class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                >
                    Download Invoice
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Order Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Order Items -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Order Items</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="item in order?.items || []" :key="item.id">
                                    <tr>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center">
                                                <div class="h-12 w-12 bg-gray-100 rounded flex items-center justify-center mr-3">
                                                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900" x-text="item.product?.name || 'Product'"></p>
                                                    <p class="text-xs text-gray-500" x-text="item.product?.category"></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-900" x-text="item.product?.sku"></td>
                                        <td class="px-4 py-4 text-sm text-gray-900" x-text="item.quantity + ' units'"></td>
                                        <td class="px-4 py-4 text-sm text-gray-900" x-text="formatCurrency(item.price)"></td>
                                        <td class="px-4 py-4 text-sm font-semibold text-gray-900" x-text="formatCurrency(item.price * item.quantity)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Order Timeline -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Order Timeline</h2>
                    <div class="space-y-4">
                        <template x-for="(event, index) in orderTimeline" :key="index">
                            <div class="flex">
                                <div class="flex flex-col items-center mr-4">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="event.color">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" x-html="event.icon"></svg>
                                    </div>
                                    <div x-show="index < orderTimeline.length - 1" class="w-0.5 h-full bg-gray-300 mt-2"></div>
                                </div>
                                <div class="pb-8">
                                    <p class="text-sm font-semibold text-gray-900" x-text="event.title"></p>
                                    <p class="text-xs text-gray-600" x-text="event.date"></p>
                                    <p class="text-sm text-gray-700 mt-1" x-text="event.description"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Shipping Information -->
                <div x-show="order?.shipping_info" class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Shipping Information</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Carrier</p>
                            <p class="text-sm font-semibold text-gray-900" x-text="order?.shipping_info?.carrier || 'N/A'"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Tracking Number</p>
                            <p class="text-sm font-semibold text-gray-900" x-text="order?.shipping_info?.tracking_number || 'N/A'"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Estimated Delivery</p>
                            <p class="text-sm font-semibold text-gray-900" x-text="formatDate(order?.shipping_info?.estimated_delivery) || 'N/A'"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Shipped Date</p>
                            <p class="text-sm font-semibold text-gray-900" x-text="formatDate(order?.shipping_info?.shipped_at) || 'N/A'"></p>
                        </div>
                    </div>
                    <div x-show="order?.shipping_info?.tracking_url" class="mt-4">
                        <a :href="order?.shipping_info?.tracking_url" target="_blank" class="text-blue-600 hover:underline text-sm">
                            Track Shipment →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Column - Summary & Info -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Order Summary -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Order Summary</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-semibold" x-text="formatCurrency(order?.subtotal || 0)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tax</span>
                            <span class="font-semibold" x-text="formatCurrency(order?.tax || 0)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Shipping</span>
                            <span class="font-semibold" x-text="formatCurrency(order?.shipping_cost || 0)"></span>
                        </div>
                        <div class="flex justify-between border-t pt-3">
                            <span class="text-lg font-bold text-gray-900">Total</span>
                            <span class="text-lg font-bold text-gray-900" x-text="formatCurrency(order?.total || 0)"></span>
                        </div>
                    </div>
                </div>

                <!-- Delivery Address -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Delivery Address</h2>
                    <div class="text-sm text-gray-700 space-y-1">
                        <p x-text="order?.delivery_address?.street_address"></p>
                        <p x-text="order?.delivery_address?.city + ', ' + (order?.delivery_address?.state || '')"></p>
                        <p x-text="order?.delivery_address?.postal_code"></p>
                        <p x-text="order?.delivery_address?.country"></p>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Payment</h2>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Method</span>
                            <span class="font-semibold" x-text="order?.payment_method || 'N/A'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Status</span>
                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold" :class="getPaymentStatusClass(order?.payment_status)" x-text="order?.payment_status || 'N/A'"></span>
                        </div>
                        <div x-show="order?.payment_date" class="flex justify-between">
                            <span class="text-gray-600">Paid on</span>
                            <span class="font-semibold text-sm" x-text="formatDate(order?.payment_date)"></span>
                        </div>
                    </div>
                </div>

                <!-- Contact Support -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-blue-900">Need Help?</p>
                            <p class="text-xs text-blue-700 mt-1">Contact support for order assistance</p>
                            <button class="mt-2 text-xs text-blue-600 hover:underline">Contact Support</button>
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
function orderDetailData(orderId) {
    return {
        order: null,
        loading: true,
        orderTimeline: [],

        async init() {
            await this.loadOrder();
            this.buildTimeline();
        },

        async loadOrder() {
            this.loading = true;
            try {
                const data = await api.getOrder(orderId);
                this.order = data;
            } catch (error) {
                console.error('Failed to load order:', error);
                alert('Failed to load order details');
            } finally {
                this.loading = false;
            }
        },

        buildTimeline() {
            const timeline = [];
            const statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
            const currentStatusIndex = statuses.indexOf(this.order?.status);

            const statusInfo = {
                pending: {
                    title: 'Order Placed',
                    description: 'Your order has been received and is pending confirmation',
                    icon: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>',
                    color: 'bg-gray-400'
                },
                confirmed: {
                    title: 'Order Confirmed',
                    description: 'Your order has been confirmed and is being prepared',
                    icon: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>',
                    color: 'bg-blue-500'
                },
                processing: {
                    title: 'Processing',
                    description: 'Your order is being processed and packaged',
                    icon: '<path d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z"/>',
                    color: 'bg-yellow-500'
                },
                shipped: {
                    title: 'Shipped',
                    description: 'Your order has been shipped and is on the way',
                    icon: '<path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>',
                    color: 'bg-purple-500'
                },
                delivered: {
                    title: 'Delivered',
                    description: 'Your order has been delivered successfully',
                    icon: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>',
                    color: 'bg-green-500'
                },
                cancelled: {
                    title: 'Cancelled',
                    description: 'This order has been cancelled',
                    icon: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>',
                    color: 'bg-red-500'
                }
            };

            if (this.order?.status === 'cancelled') {
                timeline.push({
                    ...statusInfo['pending'],
                    date: this.formatDate(this.order.created_at)
                });
                timeline.push({
                    ...statusInfo['cancelled'],
                    date: this.formatDate(this.order.updated_at)
                });
            } else {
                for (let i = 0; i <= currentStatusIndex; i++) {
                    const status = statuses[i];
                    timeline.push({
                        ...statusInfo[status],
                        date: i === currentStatusIndex ? this.formatDate(this.order.updated_at) : this.formatDate(this.order.created_at)
                    });
                }
            }

            this.orderTimeline = timeline;
        },

        async cancelOrder() {
            if (!confirm('Are you sure you want to cancel this order?')) return;

            try {
                await api.cancelOrder(this.order.id);
                alert('Order cancelled successfully!');
                await this.loadOrder();
                this.buildTimeline();
            } catch (error) {
                console.error('Failed to cancel order:', error);
                alert('Failed to cancel order. Please try again or contact support.');
            }
        },

        async reorder() {
            if (!confirm('Add all items from this order to your cart?')) return;

            try {
                await api.reorder(this.order.id);
                alert('Items added to cart successfully!');
                window.location.href = '/vendor/cart';
            } catch (error) {
                console.error('Failed to reorder:', error);
                alert('Failed to reorder. Please try again.');
            }
        },

        downloadInvoice() {
            window.open(`/api/vendor/orders/${this.order.id}/invoice`, '_blank');
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'MAD'
            }).format(amount);
        },

        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        getStatusClass(status) {
            const classes = {
                pending: 'bg-gray-100 text-gray-800',
                confirmed: 'bg-blue-100 text-blue-800',
                processing: 'bg-yellow-100 text-yellow-800',
                shipped: 'bg-purple-100 text-purple-800',
                delivered: 'bg-green-100 text-green-800',
                cancelled: 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },

        getPaymentStatusClass(status) {
            const classes = {
                pending: 'bg-yellow-100 text-yellow-800',
                paid: 'bg-green-100 text-green-800',
                failed: 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }
    };
}
</script>
@endpush

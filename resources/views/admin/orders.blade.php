@extends('layouts.app')

@section('title', 'Orders Management')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div x-data="ordersData()" x-init="init()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Orders Management</h1>
        <p class="text-gray-600 mt-1">Process and manage all platform orders</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-6 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Total Orders</p>
            <p class="text-2xl font-bold text-gray-900" x-text="stats.total || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Pending</p>
            <p class="text-2xl font-bold text-yellow-600" x-text="stats.pending || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Confirmed</p>
            <p class="text-2xl font-bold text-blue-600" x-text="stats.confirmed || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Processing</p>
            <p class="text-2xl font-bold text-purple-600" x-text="stats.processing || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Shipped</p>
            <p class="text-2xl font-bold text-indigo-600" x-text="stats.shipped || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Delivered</p>
            <p class="text-2xl font-bold text-green-600" x-text="stats.delivered || 0"></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <input
                    type="text"
                    x-model="searchQuery"
                    @input.debounce="loadOrders()"
                    placeholder="Search orders..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
            </div>
            <div>
                <select
                    x-model="statusFilter"
                    @change="loadOrders()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <input
                    type="date"
                    x-model="dateFrom"
                    @change="loadOrders()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
            </div>
            <div>
                <input
                    type="date"
                    x-model="dateTo"
                    @change="loadOrders()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
            </div>
            <div>
                <select
                    x-model="sortBy"
                    @change="loadOrders()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="-created_at">Newest First</option>
                    <option value="created_at">Oldest First</option>
                    <option value="-total_amount">Amount (High-Low)</option>
                    <option value="total_amount">Amount (Low-High)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="order in orders" :key="order.id">
                        <tr class="hover:bg-gray-50" :class="order.status === 'pending' ? 'bg-yellow-50' : ''">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-blue-600" x-text="'#' + order.id"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900" x-text="order.vendor?.name || 'N/A'"></div>
                                <div class="text-sm text-gray-500" x-text="order.vendor?.email || ''"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="new Date(order.created_at).toLocaleDateString()"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="order.items_count + ' items'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900" x-text="formatCurrency(order.total_amount)"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(order.status)" x-text="order.status"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex space-x-2">
                                    <button @click="viewOrder(order)" class="text-blue-600 hover:underline">View</button>
                                    <button x-show="order.status === 'pending'" @click="confirmOrder(order.id)" class="text-green-600 hover:underline">Confirm</button>
                                    <button x-show="order.status === 'confirmed'" @click="processOrder(order.id)" class="text-purple-600 hover:underline">Process</button>
                                    <button x-show="order.status === 'processing'" @click="showShipModal(order)" class="text-indigo-600 hover:underline">Ship</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && orders.length === 0">
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">No orders found</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Loading -->
        <div x-show="loading" class="p-8 text-center">
            <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>

    <!-- Pagination -->
    <div x-show="pagination.last_page > 1" class="mt-6 flex items-center justify-center space-x-2">
        <button
            @click="loadPage(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50"
        >
            Previous
        </button>
        <span class="px-4 py-2 text-sm text-gray-700">
            Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span>
        </span>
        <button
            @click="loadPage(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page"
            class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50"
        >
            Next
        </button>
    </div>

    <!-- Ship Order Modal -->
    <div
        x-show="showShippingModal"
        x-transition
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        @click.self="showShippingModal = false"
    >
        <div class="relative top-20 mx-auto p-8 border w-full max-w-md shadow-lg rounded-lg bg-white">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Ship Order</h3>
            <form @submit.prevent="shipOrder()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Carrier</label>
                        <input
                            type="text"
                            x-model="shippingData.carrier"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="e.g., FedEx, DHL"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tracking Number</label>
                        <input
                            type="text"
                            x-model="shippingData.tracking_number"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estimated Delivery</label>
                        <input
                            type="date"
                            x-model="shippingData.estimated_delivery"
                            :min="new Date().toISOString().split('T')[0]"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <textarea
                            x-model="shippingData.notes"
                            rows="2"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        ></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4">
                        <button
                            type="button"
                            @click="showShippingModal = false"
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                        >
                            Ship Order
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- View Order Modal -->
    <div
        x-show="showViewModal"
        x-transition
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        @click.self="showViewModal = false"
    >
        <div class="relative top-20 mx-auto p-8 border w-full max-w-4xl shadow-lg rounded-lg bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Order Details</h3>
                <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>

            <div x-show="selectedOrder">
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <p class="text-sm text-gray-600">Order ID</p>
                        <p class="text-lg font-semibold" x-text="'#' + (selectedOrder?.id || '')"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Status</p>
                        <span class="inline-block px-3 py-1 text-sm rounded-full mt-1" :class="getStatusClass(selectedOrder?.status)" x-text="selectedOrder?.status"></span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Vendor</p>
                        <p class="text-lg font-medium" x-text="selectedOrder?.vendor?.name"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Order Date</p>
                        <p class="text-lg" x-text="selectedOrder?.created_at ? new Date(selectedOrder.created_at).toLocaleDateString() : ''"></p>
                    </div>
                </div>

                <div class="border-t pt-4 mb-4">
                    <h4 class="font-semibold text-lg mb-3">Order Items</h4>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="item in selectedOrder?.items || []" :key="item.id">
                                <tr>
                                    <td class="px-4 py-3 text-sm" x-text="item.product?.name"></td>
                                    <td class="px-4 py-3 text-sm" x-text="formatCurrency(item.price)"></td>
                                    <td class="px-4 py-3 text-sm" x-text="item.quantity"></td>
                                    <td class="px-4 py-3 text-sm font-semibold" x-text="formatCurrency(item.price * item.quantity)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="border-t pt-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-600">Subtotal:</span>
                        <span class="font-medium" x-text="formatCurrency(selectedOrder?.subtotal_amount || 0)"></span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-600">Tax:</span>
                        <span class="font-medium" x-text="formatCurrency(selectedOrder?.tax_amount || 0)"></span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-600">Shipping:</span>
                        <span class="font-medium" x-text="formatCurrency(selectedOrder?.shipping_amount || 0)"></span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t">
                        <span class="text-lg font-bold">Total:</span>
                        <span class="text-lg font-bold text-blue-600" x-text="formatCurrency(selectedOrder?.total_amount || 0)"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ordersData() {
    return {
        orders: [],
        stats: {},
        loading: false,
        showShippingModal: false,
        showViewModal: false,
        selectedOrder: null,
        searchQuery: '',
        statusFilter: '',
        dateFrom: '',
        dateTo: '',
        sortBy: '-created_at',
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 20
        },
        shippingData: {
            order_id: null,
            carrier: '',
            tracking_number: '',
            estimated_delivery: '',
            notes: ''
        },

        async init() {
            await this.loadOrders();
            await this.loadStats();
        },

        async loadOrders() {
            this.loading = true;
            try {
                const params = {
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    sort: this.sortBy
                };

                if (this.searchQuery) params.search = this.searchQuery;
                if (this.statusFilter) params.status = this.statusFilter;
                if (this.dateFrom) params.date_from = this.dateFrom;
                if (this.dateTo) params.date_to = this.dateTo;

                const data = await api.adminGetOrders(params);
                this.orders = data.data || [];
                this.pagination = data.meta || this.pagination;
            } catch (error) {
                console.error('Failed to load orders:', error);
                this.orders = [];
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const data = await api.adminGetOrderStats();
                this.stats = data || {};
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async confirmOrder(orderId) {
            if (!confirm('Confirm this order?')) return;

            try {
                await api.adminConfirmOrder(orderId);
                alert('Order confirmed successfully!');
                await this.loadOrders();
                await this.loadStats();
            } catch (error) {
                console.error('Failed to confirm order:', error);
                alert('Failed to confirm order.');
            }
        },

        async processOrder(orderId) {
            if (!confirm('Start processing this order?')) return;

            try {
                await api.client.post(`/admin/orders/${orderId}/start-processing`);
                alert('Order processing started!');
                await this.loadOrders();
                await this.loadStats();
            } catch (error) {
                console.error('Failed to process order:', error);
                alert('Failed to process order.');
            }
        },

        showShipModal(order) {
            this.shippingData = {
                order_id: order.id,
                carrier: '',
                tracking_number: '',
                estimated_delivery: '',
                notes: ''
            };
            this.showShippingModal = true;
        },

        async shipOrder() {
            try {
                await api.adminShipOrder(this.shippingData.order_id, this.shippingData);
                this.showShippingModal = false;
                alert('Order shipped successfully!');
                await this.loadOrders();
                await this.loadStats();
            } catch (error) {
                console.error('Failed to ship order:', error);
                alert('Failed to ship order.');
            }
        },

        async viewOrder(order) {
            try {
                const data = await api.adminGetOrder(order.id);
                this.selectedOrder = data;
                this.showViewModal = true;
            } catch (error) {
                console.error('Failed to load order details:', error);
                alert('Failed to load order details.');
            }
        },

        async loadPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.pagination.current_page = page;
            await this.loadOrders();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'MAD'
            }).format(amount);
        },

        getStatusClass(status) {
            const classes = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'confirmed': 'bg-blue-100 text-blue-800',
                'processing': 'bg-purple-100 text-purple-800',
                'shipped': 'bg-indigo-100 text-indigo-800',
                'delivered': 'bg-green-100 text-green-800',
                'cancelled': 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }
    };
}
</script>
@endpush

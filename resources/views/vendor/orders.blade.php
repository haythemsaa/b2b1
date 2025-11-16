@extends('layouts.app')

@section('title', 'Orders')

@section('sidebar')
    @include('vendor.partials.sidebar')
@endsection

@section('content')
<div x-data="ordersData()" x-init="init()">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Orders</h1>
            <p class="text-gray-600 mt-1">View and manage your orders</p>
        </div>
        <a href="/vendor/products" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
            New Order
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <select
                    x-model="statusFilter"
                    @change="loadOrders()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Statuses</option>
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
                    placeholder="From date"
                >
            </div>
            <div>
                <input
                    type="date"
                    x-model="dateTo"
                    @change="loadOrders()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="To date"
                >
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="order in orders" :key="order.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a :href="`/vendor/orders/${order.id}`" class="text-sm font-medium text-blue-600 hover:underline" x-text="'#' + order.id"></a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="new Date(order.created_at).toLocaleDateString()"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="order.items_count + ' items'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="formatCurrency(order.total_amount)"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(order.status)" x-text="order.status"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button @click="viewOrder(order.id)" class="text-blue-600 hover:underline mr-3">View</button>
                                <button x-show="order.status === 'delivered'" @click="reorder(order.id)" class="text-green-600 hover:underline">Reorder</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && orders.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">No orders found</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Loading State -->
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
</div>
@endsection

@push('scripts')
<script>
function ordersData() {
    return {
        orders: [],
        loading: false,
        statusFilter: '',
        dateFrom: '',
        dateTo: '',
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0
        },

        async init() {
            await this.loadOrders();
        },

        async loadOrders() {
            this.loading = true;
            try {
                const params = {
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    sort: '-created_at'
                };

                if (this.statusFilter) params.status = this.statusFilter;
                if (this.dateFrom) params.date_from = this.dateFrom;
                if (this.dateTo) params.date_to = this.dateTo;

                const data = await api.getOrders(params);
                this.orders = data.data || [];
                this.pagination = data.meta || this.pagination;
            } catch (error) {
                console.error('Failed to load orders:', error);
                this.orders = [];
            } finally {
                this.loading = false;
            }
        },

        async loadPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.pagination.current_page = page;
            await this.loadOrders();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        viewOrder(id) {
            window.location.href = `/vendor/orders/${id}`;
        },

        async reorder(orderId) {
            try {
                await api.reorder(orderId);
                alert('Order has been reordered successfully!');
                await this.loadOrders();
            } catch (error) {
                console.error('Failed to reorder:', error);
                alert('Failed to reorder. Please try again.');
            }
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

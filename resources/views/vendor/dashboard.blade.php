@extends('layouts.app')

@section('title', 'Vendor Dashboard')

@section('sidebar')
    <div class="space-y-1">
        <a href="/vendor/dashboard" class="flex items-center px-3 py-2 text-white bg-blue-600 rounded-lg">
            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
            </svg>
            Dashboard
        </a>
        <a href="/vendor/products" class="flex items-center px-3 py-2 text-gray-300 rounded-lg hover:bg-gray-800">
            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
            </svg>
            Products
        </a>
        <a href="/vendor/orders" class="flex items-center px-3 py-2 text-gray-300 rounded-lg hover:bg-gray-800">
            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
            </svg>
            Orders
        </a>
        <a href="/vendor/rfqs" class="flex items-center px-3 py-2 text-gray-300 rounded-lg hover:bg-gray-800">
            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
            RFQs
        </a>
        <a href="/vendor/analytics" class="flex items-center px-3 py-2 text-gray-300 rounded-lg hover:bg-gray-800">
            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
            </svg>
            Analytics
        </a>
        <a href="/vendor/recommendations" class="flex items-center px-3 py-2 text-gray-300 rounded-lg hover:bg-gray-800">
            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            AI Recommendations
        </a>
        <a href="/vendor/approvals" class="flex items-center px-3 py-2 text-gray-300 rounded-lg hover:bg-gray-800">
            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            Approvals
        </a>
        <a href="/vendor/documents" class="flex items-center px-3 py-2 text-gray-300 rounded-lg hover:bg-gray-800">
            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
            </svg>
            Documents
        </a>
    </div>
@endsection

@section('content')
<div x-data="dashboardData()" x-init="init()">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600 mt-1">Welcome back! Here's what's happening with your account.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Orders -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Orders</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="stats.total_orders || 0"></p>
                    <p class="text-xs text-green-600 mt-1" x-text="stats.orders_trend || ''"></p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Revenue -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Revenue</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="formatCurrency(stats.total_revenue || 0)"></p>
                    <p class="text-xs text-green-600 mt-1" x-text="stats.revenue_trend || ''"></p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pending Approvals</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="stats.pending_approvals || 0"></p>
                    <a href="/vendor/approvals" class="text-xs text-blue-600 mt-1 inline-block hover:underline">View all</a>
                </div>
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <svg class="w-8 h-8 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active RFQs -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Active RFQs</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="stats.active_rfqs || 0"></p>
                    <a href="/vendor/rfqs" class="text-xs text-blue-600 mt-1 inline-block hover:underline">View all</a>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Revenue Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue Trend</h3>
            <canvas id="revenueChart" height="250"></canvas>
        </div>

        <!-- Orders Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Orders by Status</h3>
            <canvas id="ordersChart" height="250"></canvas>
        </div>
    </div>

    <!-- AI Recommendations -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Personalized Recommendations -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Recommended for You</h3>
                <a href="/vendor/recommendations" class="text-sm text-blue-600 hover:underline">View all</a>
            </div>
            <div class="space-y-3">
                <template x-for="product in recommendations.slice(0, 5)" :key="product.id">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900" x-text="product.name"></p>
                            <p class="text-sm text-gray-600" x-text="`Confidence: ${(product.confidence * 100).toFixed(0)}%`"></p>
                        </div>
                        <button class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                            View
                        </button>
                    </div>
                </template>
                <div x-show="recommendations.length === 0" class="text-center py-8 text-gray-500">
                    No recommendations available yet
                </div>
            </div>
        </div>

        <!-- Order Predictions -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Predicted Reorders</h3>
                <a href="/vendor/recommendations" class="text-sm text-blue-600 hover:underline">View all</a>
            </div>
            <div class="space-y-3">
                <template x-for="prediction in predictions.slice(0, 5)" :key="prediction.id">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900" x-text="prediction.product_name"></p>
                            <p class="text-sm text-gray-600" x-text="`Predicted: ${prediction.predicted_date}`"></p>
                        </div>
                        <button class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                            Reorder
                        </button>
                    </div>
                </template>
                <div x-show="predictions.length === 0" class="text-center py-8 text-gray-500">
                    No predictions available yet
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Recent Orders</h3>
                <a href="/vendor/orders" class="text-sm text-blue-600 hover:underline">View all</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="order in recentOrders" :key="order.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="'#' + order.id"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="new Date(order.created_at).toLocaleDateString()"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatCurrency(order.total_amount)"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(order.status)" x-text="order.status"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a :href="`/vendor/orders/${order.id}`" class="text-blue-600 hover:underline">View</a>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="recentOrders.length === 0">
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">No orders found</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dashboardData() {
    return {
        stats: {},
        recommendations: [],
        predictions: [],
        recentOrders: [],
        charts: {},

        async init() {
            await this.loadStats();
            await this.loadRecommendations();
            await this.loadPredictions();
            await this.loadRecentOrders();
            await this.loadAnalytics();
        },

        async loadStats() {
            try {
                const data = await api.getOrderStats();
                this.stats = data;
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async loadRecommendations() {
            try {
                const data = await api.getPersonalizedRecommendations();
                this.recommendations = data.data || [];
            } catch (error) {
                console.error('Failed to load recommendations:', error);
                this.recommendations = [];
            }
        },

        async loadPredictions() {
            try {
                const data = await api.getOrderPredictions();
                this.predictions = data.data || [];
            } catch (error) {
                console.error('Failed to load predictions:', error);
                this.predictions = [];
            }
        },

        async loadRecentOrders() {
            try {
                const data = await api.getOrders({ limit: 5, sort: '-created_at' });
                this.recentOrders = data.data || [];
            } catch (error) {
                console.error('Failed to load recent orders:', error);
                this.recentOrders = [];
            }
        },

        async loadAnalytics() {
            try {
                const data = await api.getDashboard('monthly');

                // Revenue Chart
                const revenueCtx = document.getElementById('revenueChart').getContext('2d');
                this.charts.revenue = new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: data.historical?.map(h => h.period) || [],
                        datasets: [{
                            label: 'Revenue',
                            data: data.historical?.map(h => h.revenue || 0) || [],
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });

                // Orders Chart
                const ordersCtx = document.getElementById('ordersChart').getContext('2d');
                this.charts.orders = new Chart(ordersCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Pending', 'Confirmed', 'Shipped', 'Delivered'],
                        datasets: [{
                            data: [
                                data.current_period?.pending_orders || 0,
                                data.current_period?.confirmed_orders || 0,
                                data.current_period?.shipped_orders || 0,
                                data.current_period?.delivered_orders || 0
                            ],
                            backgroundColor: [
                                'rgb(251, 191, 36)',
                                'rgb(59, 130, 246)',
                                'rgb(139, 92, 246)',
                                'rgb(34, 197, 94)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            } catch (error) {
                console.error('Failed to load analytics:', error);
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

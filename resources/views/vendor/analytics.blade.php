@extends('layouts.app')

@section('title', 'Analytics Dashboard')

@section('sidebar')
    @include('vendor.partials.sidebar')
@endsection

@section('content')
<div x-data="analyticsData()" x-init="init()">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Analytics Dashboard</h1>
            <p class="text-gray-600 mt-1">Track your performance and insights</p>
        </div>
        <div class="flex items-center space-x-3">
            <select
                x-model="period"
                @change="loadAnalytics()"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
            </select>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Revenue -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-blue-100 text-sm">Total Revenue</p>
                    <p class="text-3xl font-bold" x-text="formatCurrency(currentPeriod.revenue || 0)"></p>
                </div>
                <div class="p-3 bg-blue-400 rounded-lg">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" :class="trends.revenue >= 0 ? 'text-green-300' : 'text-red-300'" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm" x-text="Math.abs(trends.revenue || 0).toFixed(1) + '% vs last period'"></span>
            </div>
        </div>

        <!-- Orders -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-green-100 text-sm">Total Orders</p>
                    <p class="text-3xl font-bold" x-text="currentPeriod.orders_count || 0"></p>
                </div>
                <div class="p-3 bg-green-400 rounded-lg">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-center">
                <span class="text-sm" x-text="Math.abs(trends.orders || 0).toFixed(1) + '% vs last period'"></span>
            </div>
        </div>

        <!-- Avg Order Value -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-purple-100 text-sm">Avg Order Value</p>
                    <p class="text-3xl font-bold" x-text="formatCurrency(currentPeriod.avg_order_value || 0)"></p>
                </div>
                <div class="p-3 bg-purple-400 rounded-lg">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-center">
                <span class="text-sm" x-text="Math.abs(trends.avg_order || 0).toFixed(1) + '% vs last period'"></span>
            </div>
        </div>

        <!-- Products Ordered -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-orange-100 text-sm">Products Ordered</p>
                    <p class="text-3xl font-bold" x-text="currentPeriod.products_ordered || 0"></p>
                </div>
                <div class="p-3 bg-orange-400 rounded-lg">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-center">
                <span class="text-sm" x-text="'Across ' + (currentPeriod.orders_count || 0) + ' orders'"></span>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Revenue Trend -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue Trend</h3>
            <canvas id="revenueTrendChart" height="250"></canvas>
        </div>

        <!-- Orders Trend -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Orders Trend</h3>
            <canvas id="ordersTrendChart" height="250"></canvas>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Order Status Distribution -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Status Distribution</h3>
            <canvas id="statusChart" height="250"></canvas>
        </div>

        <!-- Category Performance -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Categories</h3>
            <canvas id="categoryChart" height="250"></canvas>
        </div>
    </div>

    <!-- Top Products Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Top Performing Products</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="(product, index) in topProducts" :key="product.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-sm font-semibold text-blue-600" x-text="index + 1"></span>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900" x-text="product.name"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="product.category"></td>
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="product.orders_count"></td>
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="product.total_quantity"></td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900" x-text="formatCurrency(product.total_revenue)"></td>
                        </tr>
                    </template>
                    <tr x-show="topProducts.length === 0">
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">No data available</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function analyticsData() {
    return {
        period: 'monthly',
        currentPeriod: {},
        previousPeriod: {},
        trends: {},
        historical: [],
        topProducts: [],
        charts: {},

        async init() {
            await this.loadAnalytics();
            await this.loadTopProducts();
            this.initCharts();
        },

        async loadAnalytics() {
            try {
                const data = await api.getDashboard(this.period);

                this.currentPeriod = data.current_period || {};
                this.previousPeriod = data.previous_period || {};
                this.trends = data.trends || {};
                this.historical = data.historical || [];

                // Update charts
                this.updateCharts();
            } catch (error) {
                console.error('Failed to load analytics:', error);
            }
        },

        async loadTopProducts() {
            try {
                const data = await api.getTopProducts({ period: this.period, limit: 10 });
                this.topProducts = data.data || [];
            } catch (error) {
                console.error('Failed to load top products:', error);
                this.topProducts = [];
            }
        },

        initCharts() {
            // Revenue Trend Chart
            const revenueCtx = document.getElementById('revenueTrendChart').getContext('2d');
            this.charts.revenue = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Revenue',
                        data: [],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
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

            // Orders Trend Chart
            const ordersCtx = document.getElementById('ordersTrendChart').getContext('2d');
            this.charts.orders = new Chart(ordersCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Orders',
                        data: [],
                        backgroundColor: 'rgb(34, 197, 94)',
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

            // Status Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            this.charts.status = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Confirmed', 'Shipped', 'Delivered'],
                    datasets: [{
                        data: [0, 0, 0, 0],
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

            // Category Chart
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            this.charts.category = new Chart(categoryCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Revenue',
                        data: [],
                        backgroundColor: 'rgb(139, 92, 246)',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        },

        updateCharts() {
            // Update Revenue Trend
            if (this.charts.revenue) {
                this.charts.revenue.data.labels = this.historical.map(h => h.period);
                this.charts.revenue.data.datasets[0].data = this.historical.map(h => h.revenue || 0);
                this.charts.revenue.update();
            }

            // Update Orders Trend
            if (this.charts.orders) {
                this.charts.orders.data.labels = this.historical.map(h => h.period);
                this.charts.orders.data.datasets[0].data = this.historical.map(h => h.orders_count || 0);
                this.charts.orders.update();
            }

            // Update Status Chart
            if (this.charts.status) {
                this.charts.status.data.datasets[0].data = [
                    this.currentPeriod.pending_orders || 0,
                    this.currentPeriod.confirmed_orders || 0,
                    this.currentPeriod.shipped_orders || 0,
                    this.currentPeriod.delivered_orders || 0
                ];
                this.charts.status.update();
            }
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'MAD'
            }).format(amount);
        }
    };
}
</script>
@endpush

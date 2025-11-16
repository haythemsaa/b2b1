@extends('layouts.app')

@section('title', 'Vendor Details')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div x-data="adminVendorDetailData({{ $id }})" x-init="init()">
    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-20">
        <svg class="animate-spin h-12 w-12 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    <!-- Vendor Content -->
    <div x-show="!loading && vendor" class="max-w-7xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="mb-6 text-sm">
            <ol class="flex items-center space-x-2 text-gray-600">
                <li><a href="/admin/vendors" class="hover:text-blue-600">Vendors</a></li>
                <li><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg></li>
                <li class="text-gray-900 font-medium" x-text="vendor?.company_name"></li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="h-16 w-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-2xl font-bold mr-4">
                        <span x-text="vendor?.company_name?.charAt(0)"></span>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900" x-text="vendor?.company_name"></h1>
                        <p class="text-gray-600" x-text="vendor?.email"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-4 py-2 rounded-full text-sm font-semibold" :class="getStatusClass(vendor?.status)" x-text="vendor?.status?.toUpperCase()"></span>
                    <button @click="toggleStatus()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        <span x-show="vendor?.status === 'active'">Suspend</span>
                        <span x-show="vendor?.status !== 'active'">Activate</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow p-6 text-white">
                <p class="text-sm opacity-90 mb-1">Total Orders</p>
                <p class="text-3xl font-bold" x-text="stats.total_orders || 0"></p>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow p-6 text-white">
                <p class="text-sm opacity-90 mb-1">Total Spent</p>
                <p class="text-3xl font-bold" x-text="formatCurrency(stats.total_spent || 0)"></p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow p-6 text-white">
                <p class="text-sm opacity-90 mb-1">Credit Limit</p>
                <p class="text-3xl font-bold" x-text="formatCurrency(vendor?.credit_limit || 0)"></p>
            </div>
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow p-6 text-white">
                <p class="text-sm opacity-90 mb-1">Credit Used</p>
                <p class="text-3xl font-bold" x-text="formatCurrency(stats.credit_used || 0)"></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Company Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Company Information</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Company Name</p>
                            <p class="font-semibold" x-text="vendor?.company_name"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Tax ID</p>
                            <p class="font-semibold" x-text="vendor?.tax_id || 'N/A'"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Email</p>
                            <p class="font-semibold" x-text="vendor?.email"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Phone</p>
                            <p class="font-semibold" x-text="vendor?.phone || 'N/A'"></p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-sm text-gray-600">Address</p>
                            <p class="font-semibold" x-text="getFullAddress()"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Vendor Group</p>
                            <p class="font-semibold" x-text="vendor?.vendor_group || 'Standard'"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Member Since</p>
                            <p class="font-semibold" x-text="formatDate(vendor?.created_at)"></p>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Recent Orders</h2>
                        <a href="/admin/orders" class="text-blue-600 hover:underline text-sm">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Order ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Total</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="order in recentOrders" :key="order.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <a :href="`/admin/orders/${order.id}`" class="text-blue-600 hover:underline" x-text="'#' + order.id"></a>
                                        </td>
                                        <td class="px-4 py-3 text-sm" x-text="formatDate(order.created_at)"></td>
                                        <td class="px-4 py-3 text-sm font-semibold" x-text="formatCurrency(order.total_amount)"></td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs rounded-full" :class="getOrderStatusClass(order.status)" x-text="order.status"></span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="recentOrders.length === 0">
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">No orders yet</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Activity Log -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Activity Log</h2>
                    <div class="space-y-3">
                        <template x-for="activity in activityLog" :key="activity.id">
                            <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                                <div class="h-8 w-8 rounded-full flex items-center justify-center mr-3" :class="activity.color">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <circle cx="10" cy="10" r="3"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900" x-text="activity.description"></p>
                                    <p class="text-xs text-gray-600" x-text="activity.timestamp"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
                    <div class="space-y-2">
                        <button @click="sendEmail()" class="w-full text-left px-4 py-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                            <svg class="w-4 h-4 inline mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                            Send Email
                        </button>
                        <button @click="adjustCreditLimit()" class="w-full text-left px-4 py-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                            <svg class="w-4 h-4 inline mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                            </svg>
                            Adjust Credit Limit
                        </button>
                        <button @click="viewDocuments()" class="w-full text-left px-4 py-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                            <svg class="w-4 h-4 inline mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                            </svg>
                            View Documents
                        </button>
                        <button @click="generateReport()" class="w-full text-left px-4 py-3 bg-orange-50 hover:bg-orange-100 rounded-lg transition-colors">
                            <svg class="w-4 h-4 inline mr-2 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                            </svg>
                            Generate Report
                        </button>
                    </div>
                </div>

                <!-- Payment Terms -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Payment Terms</h2>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-600">Payment Method</p>
                            <p class="font-semibold" x-text="vendor?.payment_terms || 'NET 30'"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Credit Limit</p>
                            <p class="font-semibold" x-text="formatCurrency(vendor?.credit_limit || 0)"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Credit Used</p>
                            <div class="mt-2">
                                <div class="flex justify-between text-sm mb-1">
                                    <span x-text="formatCurrency(stats.credit_used || 0)"></span>
                                    <span x-text="getCreditPercentage() + '%'"></span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all" :class="getCreditBarClass()" :style="`width: ${getCreditPercentage()}%`"></div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Available Credit</p>
                            <p class="font-semibold text-green-600" x-text="formatCurrency((vendor?.credit_limit || 0) - (stats.credit_used || 0))"></p>
                        </div>
                    </div>
                </div>

                <!-- Contact Person -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Contact Person</h2>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-600">Name</p>
                            <p class="font-semibold" x-text="vendor?.contact_name || vendor?.company_name"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Email</p>
                            <p class="font-semibold" x-text="vendor?.email"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Phone</p>
                            <p class="font-semibold" x-text="vendor?.phone || 'N/A'"></p>
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
function adminVendorDetailData(vendorId) {
    return {
        vendor: null,
        stats: {},
        recentOrders: [],
        activityLog: [],
        loading: true,

        async init() {
            await this.loadVendor();
            await this.loadStats();
            await this.loadRecentOrders();
            this.generateActivityLog();
        },

        async loadVendor() {
            this.loading = true;
            try {
                const data = await api.adminGetVendor(vendorId);
                this.vendor = data;
            } catch (error) {
                console.error('Failed to load vendor:', error);
                alert('Failed to load vendor details');
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const data = await api.client.get(`/admin/vendors/${vendorId}/stats`);
                this.stats = data.data || {};
            } catch (error) {
                console.error('Failed to load stats:', error);
                this.stats = {};
            }
        },

        async loadRecentOrders() {
            try {
                const data = await api.client.get(`/admin/vendors/${vendorId}/orders`, {
                    params: { per_page: 5, sort: '-created_at' }
                });
                this.recentOrders = data.data?.data || [];
            } catch (error) {
                console.error('Failed to load orders:', error);
                this.recentOrders = [];
            }
        },

        generateActivityLog() {
            this.activityLog = [
                {
                    id: 1,
                    description: 'Vendor account created',
                    timestamp: this.formatDate(this.vendor?.created_at),
                    color: 'bg-blue-500'
                },
                {
                    id: 2,
                    description: `Status changed to ${this.vendor?.status}`,
                    timestamp: this.formatDate(this.vendor?.updated_at),
                    color: this.vendor?.status === 'active' ? 'bg-green-500' : 'bg-red-500'
                }
            ];
        },

        async toggleStatus() {
            const newStatus = this.vendor.status === 'active' ? 'inactive' : 'active';
            if (!confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'suspend'} this vendor?`)) return;

            try {
                await api.adminUpdateVendor(vendorId, { status: newStatus });
                await this.loadVendor();
                alert(`Vendor ${newStatus === 'active' ? 'activated' : 'suspended'} successfully!`);
            } catch (error) {
                console.error('Failed to update status:', error);
                alert('Failed to update vendor status');
            }
        },

        sendEmail() {
            window.location.href = `mailto:${this.vendor?.email}`;
        },

        async adjustCreditLimit() {
            const newLimit = prompt('Enter new credit limit:', this.vendor?.credit_limit || 0);
            if (!newLimit) return;

            try {
                await api.adminUpdateVendor(vendorId, { credit_limit: parseFloat(newLimit) });
                await this.loadVendor();
                alert('Credit limit updated successfully!');
            } catch (error) {
                console.error('Failed to update credit limit:', error);
                alert('Failed to update credit limit');
            }
        },

        viewDocuments() {
            alert('Document viewer would open here');
        },

        generateReport() {
            window.open(`/api/admin/vendors/${vendorId}/report`, '_blank');
        },

        getFullAddress() {
            if (!this.vendor) return '';
            const parts = [
                this.vendor.street_address,
                this.vendor.city,
                this.vendor.postal_code,
                this.vendor.country
            ].filter(Boolean);
            return parts.join(', ') || 'N/A';
        },

        getCreditPercentage() {
            if (!this.vendor?.credit_limit || this.vendor.credit_limit === 0) return 0;
            return Math.min(100, Math.round((this.stats.credit_used || 0) / this.vendor.credit_limit * 100));
        },

        getCreditBarClass() {
            const percentage = this.getCreditPercentage();
            if (percentage >= 90) return 'bg-red-500';
            if (percentage >= 70) return 'bg-orange-500';
            return 'bg-green-500';
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'MAD'
            }).format(amount || 0);
        },

        formatDate(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        getStatusClass(status) {
            const classes = {
                active: 'bg-green-100 text-green-800',
                inactive: 'bg-gray-100 text-gray-800',
                suspended: 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },

        getOrderStatusClass(status) {
            const classes = {
                pending: 'bg-yellow-100 text-yellow-800',
                confirmed: 'bg-blue-100 text-blue-800',
                processing: 'bg-purple-100 text-purple-800',
                shipped: 'bg-indigo-100 text-indigo-800',
                delivered: 'bg-green-100 text-green-800',
                cancelled: 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }
    };
}
</script>
@endpush

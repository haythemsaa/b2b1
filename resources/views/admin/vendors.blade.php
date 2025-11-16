@extends('layouts.app')

@section('title', 'Vendors Management')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div x-data="vendorsData()" x-init="init()">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Vendors Management</h1>
            <p class="text-gray-600 mt-1">Manage all vendors on the platform</p>
        </div>
        <button
            @click="showCreateModal = true"
            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
        >
            + Add Vendor
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Total Vendors</p>
            <p class="text-2xl font-bold text-gray-900" x-text="stats.total || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Active</p>
            <p class="text-2xl font-bold text-green-600" x-text="stats.active || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Inactive</p>
            <p class="text-2xl font-bold text-orange-600" x-text="stats.inactive || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">This Month</p>
            <p class="text-2xl font-bold text-blue-600" x-text="stats.this_month || 0"></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <input
                    type="text"
                    x-model="searchQuery"
                    @input.debounce="loadVendors()"
                    placeholder="Search vendors..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
            </div>
            <div>
                <select
                    x-model="statusFilter"
                    @change="loadVendors()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
            <div>
                <select
                    x-model="groupFilter"
                    @change="loadVendors()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Groups</option>
                    <template x-for="group in vendorGroups" :key="group.id">
                        <option :value="group.id" x-text="group.name"></option>
                    </template>
                </select>
            </div>
            <div>
                <select
                    x-model="sortBy"
                    @change="loadVendors()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="-created_at">Newest First</option>
                    <option value="created_at">Oldest First</option>
                    <option value="name">Name (A-Z)</option>
                    <option value="-name">Name (Z-A)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Vendors Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Group</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Credit Limit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="vendor in vendors" :key="vendor.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span class="text-blue-600 font-semibold" x-text="vendor.name?.charAt(0)"></span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900" x-text="vendor.name"></div>
                                        <div class="text-sm text-gray-500" x-text="vendor.email"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="vendor.vendor_profile?.company_name || 'N/A'"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="vendor.vendor_profile?.vendor_group?.name || 'None'"></td>
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="formatCurrency(vendor.vendor_profile?.credit_limit || 0)"></td>
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="vendor.orders_count || 0"></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(vendor.status)" x-text="vendor.status"></span>
                            </td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <button @click="viewVendor(vendor.id)" class="text-blue-600 hover:underline">View</button>
                                <button @click="editVendor(vendor)" class="text-green-600 hover:underline">Edit</button>
                                <button @click="toggleStatus(vendor)" class="text-orange-600 hover:underline" x-text="vendor.status === 'active' ? 'Suspend' : 'Activate'"></button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && vendors.length === 0">
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">No vendors found</td>
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

    <!-- Create/Edit Vendor Modal -->
    <div
        x-show="showCreateModal || showEditModal"
        x-transition
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        @click.self="closeModals()"
    >
        <div class="relative top-20 mx-auto p-8 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900" x-text="showEditModal ? 'Edit Vendor' : 'Create New Vendor'"></h3>
                <button @click="closeModals()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="showEditModal ? updateVendor() : createVendor()">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input
                                type="text"
                                x-model="vendorForm.name"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input
                                type="email"
                                x-model="vendorForm.email"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                            <input
                                type="text"
                                x-model="vendorForm.company_name"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input
                                type="tel"
                                x-model="vendorForm.phone"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Credit Limit</label>
                            <input
                                type="number"
                                x-model="vendorForm.credit_limit"
                                min="0"
                                step="0.01"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Term (days)</label>
                            <input
                                type="number"
                                x-model="vendorForm.payment_term"
                                min="0"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Vendor Group</label>
                        <select
                            x-model="vendorForm.vendor_group_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">No Group</option>
                            <template x-for="group in vendorGroups" :key="group.id">
                                <option :value="group.id" x-text="group.name"></option>
                            </template>
                        </select>
                    </div>

                    <div x-show="!showEditModal">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input
                            type="password"
                            x-model="vendorForm.password"
                            :required="!showEditModal"
                            minlength="8"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button
                            type="button"
                            @click="closeModals()"
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                        >
                            <span x-show="!saving" x-text="showEditModal ? 'Update' : 'Create'"></span>
                            <span x-show="saving">Saving...</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function vendorsData() {
    return {
        vendors: [],
        vendorGroups: [],
        stats: {},
        loading: false,
        saving: false,
        showCreateModal: false,
        showEditModal: false,
        searchQuery: '',
        statusFilter: '',
        groupFilter: '',
        sortBy: '-created_at',
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 20
        },
        vendorForm: {
            id: null,
            name: '',
            email: '',
            company_name: '',
            phone: '',
            credit_limit: 0,
            payment_term: 30,
            vendor_group_id: '',
            password: ''
        },

        async init() {
            await this.loadVendorGroups();
            await this.loadVendors();
            await this.loadStats();
        },

        async loadVendors() {
            this.loading = true;
            try {
                const params = {
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    sort: this.sortBy
                };

                if (this.searchQuery) params.search = this.searchQuery;
                if (this.statusFilter) params.status = this.statusFilter;
                if (this.groupFilter) params.vendor_group_id = this.groupFilter;

                const data = await api.adminGetVendors(params);
                this.vendors = data.data || [];
                this.pagination = data.meta || this.pagination;
            } catch (error) {
                console.error('Failed to load vendors:', error);
                this.vendors = [];
            } finally {
                this.loading = false;
            }
        },

        async loadVendorGroups() {
            try {
                const data = await api.client.get('/admin/vendors/groups');
                this.vendorGroups = data.data.data || [];
            } catch (error) {
                console.error('Failed to load groups:', error);
            }
        },

        async loadStats() {
            try {
                const data = await api.adminGetVendors({ per_page: 1000 });
                const vendors = data.data || [];

                this.stats = {
                    total: vendors.length,
                    active: vendors.filter(v => v.status === 'active').length,
                    inactive: vendors.filter(v => v.status === 'inactive').length,
                    this_month: vendors.filter(v => {
                        const createdAt = new Date(v.created_at);
                        const now = new Date();
                        return createdAt.getMonth() === now.getMonth() &&
                               createdAt.getFullYear() === now.getFullYear();
                    }).length
                };
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async createVendor() {
            this.saving = true;
            try {
                await api.adminCreateVendor(this.vendorForm);
                this.closeModals();
                alert('Vendor created successfully!');
                await this.loadVendors();
                await this.loadStats();
            } catch (error) {
                console.error('Failed to create vendor:', error);
                alert('Failed to create vendor. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        async updateVendor() {
            this.saving = true;
            try {
                await api.adminUpdateVendor(this.vendorForm.id, this.vendorForm);
                this.closeModals();
                alert('Vendor updated successfully!');
                await this.loadVendors();
            } catch (error) {
                console.error('Failed to update vendor:', error);
                alert('Failed to update vendor. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        editVendor(vendor) {
            this.vendorForm = {
                id: vendor.id,
                name: vendor.name,
                email: vendor.email,
                company_name: vendor.vendor_profile?.company_name || '',
                phone: vendor.phone || '',
                credit_limit: vendor.vendor_profile?.credit_limit || 0,
                payment_term: vendor.vendor_profile?.payment_term || 30,
                vendor_group_id: vendor.vendor_profile?.vendor_group_id || '',
                password: ''
            };
            this.showEditModal = true;
        },

        async toggleStatus(vendor) {
            const newStatus = vendor.status === 'active' ? 'inactive' : 'active';
            if (!confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'suspend'} this vendor?`)) return;

            try {
                await api.adminUpdateVendor(vendor.id, { status: newStatus });
                alert(`Vendor ${newStatus === 'active' ? 'activated' : 'suspended'} successfully!`);
                await this.loadVendors();
                await this.loadStats();
            } catch (error) {
                console.error('Failed to update status:', error);
                alert('Failed to update vendor status.');
            }
        },

        viewVendor(id) {
            window.location.href = `/admin/vendors/${id}`;
        },

        async loadPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.pagination.current_page = page;
            await this.loadVendors();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        closeModals() {
            this.showCreateModal = false;
            this.showEditModal = false;
            this.vendorForm = {
                id: null,
                name: '',
                email: '',
                company_name: '',
                phone: '',
                credit_limit: 0,
                payment_term: 30,
                vendor_group_id: '',
                password: ''
            };
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'MAD'
            }).format(amount);
        },

        getStatusClass(status) {
            const classes = {
                'active': 'bg-green-100 text-green-800',
                'inactive': 'bg-gray-100 text-gray-800',
                'suspended': 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }
    };
}
</script>
@endpush

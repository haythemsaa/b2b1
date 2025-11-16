@extends('layouts.app')

@section('title', 'RFQ Management')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div x-data="adminRfqsData()" x-init="init()">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">RFQ Management</h1>
            <p class="text-gray-600 mt-1">Manage and monitor all Request for Quotations</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">Total RFQs</p>
            <p class="text-3xl font-bold text-gray-900" x-text="stats.total || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">Pending</p>
            <p class="text-3xl font-bold text-yellow-600" x-text="stats.pending || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">In Review</p>
            <p class="text-3xl font-bold text-blue-600" x-text="stats.in_review || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">Completed</p>
            <p class="text-3xl font-bold text-green-600" x-text="stats.completed || 0"></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <select x-model="statusFilter" @change="loadRfqs()" class="w-full px-4 py-2 border rounded-lg">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="pending">Pending</option>
                    <option value="in_review">In Review</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <input type="text" x-model="search" @input="debounceSearch()" placeholder="Search RFQs..." class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <select x-model="sortBy" @change="loadRfqs()" class="w-full px-4 py-2 border rounded-lg">
                    <option value="-created_at">Newest First</option>
                    <option value="created_at">Oldest First</option>
                    <option value="deadline">By Deadline</option>
                </select>
            </div>
        </div>
    </div>

    <!-- RFQs Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deadline</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="rfq in rfqs" :key="rfq.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm" x-text="'#' + rfq.id"></td>
                        <td class="px-6 py-4">
                            <a :href="`/admin/rfqs/${rfq.id}`" class="text-blue-600 hover:underline font-medium" x-text="rfq.title"></a>
                        </td>
                        <td class="px-6 py-4 text-sm" x-text="rfq.vendor?.company_name"></td>
                        <td class="px-6 py-4 text-sm text-gray-600" x-text="formatDate(rfq.created_at)"></td>
                        <td class="px-6 py-4 text-sm" x-text="formatDate(rfq.deadline)"></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(rfq.status)" x-text="rfq.status"></span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a :href="`/admin/rfqs/${rfq.id}`" class="text-blue-600 hover:underline">View</a>
                        </td>
                    </tr>
                </template>
                <tr x-show="!loading && rfqs.length === 0">
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">No RFQs found</td>
                </tr>
            </tbody>
        </table>

        <div x-show="loading" class="p-8 text-center">
            <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>

    <!-- Pagination -->
    <div x-show="pagination.last_page > 1" class="mt-6 flex justify-center space-x-2">
        <button @click="loadPage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-100 disabled:opacity-50">
            Previous
        </button>
        <span class="px-4 py-2 text-sm text-gray-700">
            Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span>
        </span>
        <button @click="loadPage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-100 disabled:opacity-50">
            Next
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
function adminRfqsData() {
    return {
        rfqs: [],
        stats: {},
        loading: false,
        statusFilter: '',
        search: '',
        sortBy: '-created_at',
        pagination: {current_page: 1, last_page: 1, per_page: 20},

        async init() {
            await this.loadStats();
            await this.loadRfqs();
        },

        async loadStats() {
            try {
                const data = await api.client.get('/admin/rfqs/stats');
                this.stats = data.data;
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async loadRfqs() {
            this.loading = true;
            try {
                const params = {
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    sort: this.sortBy
                };

                if (this.statusFilter) params.status = this.statusFilter;
                if (this.search) params.search = this.search;

                const data = await api.client.get('/admin/rfqs', { params });
                this.rfqs = data.data?.data || [];
                this.pagination = data.data?.meta || this.pagination;
            } catch (error) {
                console.error('Failed to load RFQs:', error);
                this.rfqs = [];
            } finally {
                this.loading = false;
            }
        },

        async loadPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.pagination.current_page = page;
            await this.loadRfqs();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        debounceSearch: window.utils?.debounce(function() {
            this.loadRfqs();
        }, 500) || function() { this.loadRfqs(); },

        formatDate(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString();
        },

        getStatusClass(status) {
            const classes = {
                draft: 'bg-gray-100 text-gray-800',
                pending: 'bg-yellow-100 text-yellow-800',
                in_review: 'bg-blue-100 text-blue-800',
                completed: 'bg-green-100 text-green-800',
                cancelled: 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }
    };
}
</script>
@endpush

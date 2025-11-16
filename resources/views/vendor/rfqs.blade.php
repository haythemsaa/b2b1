@extends('layouts.app')

@section('title', 'RFQs - Request for Quotation')

@section('sidebar')
    @include('vendor.partials.sidebar')
@endsection

@section('content')
<div x-data="rfqsData()" x-init="init()">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Request for Quotation</h1>
            <p class="text-gray-600 mt-1">Create and manage your RFQ requests</p>
        </div>
        <button
            @click="showCreateModal = true"
            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
        >
            + New RFQ
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Total RFQs</p>
            <p class="text-2xl font-bold text-gray-900" x-text="stats.total || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Pending</p>
            <p class="text-2xl font-bold text-yellow-600" x-text="stats.pending || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Quoted</p>
            <p class="text-2xl font-bold text-blue-600" x-text="stats.quoted || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Accepted</p>
            <p class="text-2xl font-bold text-green-600" x-text="stats.accepted || 0"></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <select
                    x-model="statusFilter"
                    @change="loadRfqs()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                    <option value="quoted">Quoted</option>
                    <option value="accepted">Accepted</option>
                    <option value="rejected">Rejected</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
            <div>
                <input
                    type="text"
                    x-model="searchQuery"
                    @input.debounce="loadRfqs()"
                    placeholder="Search RFQs..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
            </div>
            <div>
                <select
                    x-model="sortBy"
                    @change="loadRfqs()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="-created_at">Newest First</option>
                    <option value="created_at">Oldest First</option>
                    <option value="-deadline">Deadline (Soon)</option>
                    <option value="deadline">Deadline (Later)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- RFQs List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">RFQ #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deadline</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quotes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="rfq in rfqs" :key="rfq.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a :href="`/vendor/rfqs/${rfq.id}`" class="text-sm font-medium text-blue-600 hover:underline" x-text="'#' + rfq.id"></a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900" x-text="rfq.title"></div>
                                <div class="text-sm text-gray-500" x-text="rfq.description?.substring(0, 50) + '...'"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="rfq.items_count || 0"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="new Date(rfq.created_at).toLocaleDateString()"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm" :class="isDeadlineSoon(rfq.deadline) ? 'text-red-600 font-semibold' : 'text-gray-600'" x-text="rfq.deadline ? new Date(rfq.deadline).toLocaleDateString() : 'N/A'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full" x-text="(rfq.quotes_count || 0) + ' quotes'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(rfq.status)" x-text="rfq.status"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                <a :href="`/vendor/rfqs/${rfq.id}`" class="text-blue-600 hover:underline">View</a>
                                <button x-show="rfq.status === 'draft'" @click="submitRfq(rfq.id)" class="text-green-600 hover:underline">Submit</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && rfqs.length === 0">
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">No RFQs found</td>
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

    <!-- Create RFQ Modal -->
    <div
        x-show="showCreateModal"
        x-transition
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        @click.self="showCreateModal = false"
    >
        <div class="relative top-20 mx-auto p-8 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Create New RFQ</h3>
                <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="createRfq()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                        <input
                            type="text"
                            x-model="newRfq.title"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="RFQ Title"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea
                            x-model="newRfq.description"
                            rows="4"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="Describe what you need..."
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Deadline</label>
                        <input
                            type="date"
                            x-model="newRfq.deadline"
                            :min="new Date().toISOString().split('T')[0]"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="creating"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                        >
                            <span x-show="!creating">Create RFQ</span>
                            <span x-show="creating">Creating...</span>
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
function rfqsData() {
    return {
        rfqs: [],
        stats: {},
        loading: false,
        creating: false,
        showCreateModal: false,
        statusFilter: '',
        searchQuery: '',
        sortBy: '-created_at',
        newRfq: {
            title: '',
            description: '',
            deadline: ''
        },

        async init() {
            await this.loadRfqs();
            await this.loadStats();
        },

        async loadRfqs() {
            this.loading = true;
            try {
                const params = {
                    sort: this.sortBy
                };

                if (this.statusFilter) params.status = this.statusFilter;
                if (this.searchQuery) params.search = this.searchQuery;

                const data = await api.getRfqs(params);
                this.rfqs = data.data || [];
            } catch (error) {
                console.error('Failed to load RFQs:', error);
                this.rfqs = [];
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const data = await api.getRfqs({ per_page: 1000 });
                const rfqs = data.data || [];

                this.stats = {
                    total: rfqs.length,
                    pending: rfqs.filter(r => r.status === 'submitted').length,
                    quoted: rfqs.filter(r => r.status === 'quoted').length,
                    accepted: rfqs.filter(r => r.status === 'accepted').length
                };
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async createRfq() {
            this.creating = true;
            try {
                await api.createRfq(this.newRfq);
                this.showCreateModal = false;
                this.newRfq = { title: '', description: '', deadline: '' };
                await this.loadRfqs();
                await this.loadStats();
                alert('RFQ created successfully!');
            } catch (error) {
                console.error('Failed to create RFQ:', error);
                alert('Failed to create RFQ. Please try again.');
            } finally {
                this.creating = false;
            }
        },

        async submitRfq(rfqId) {
            if (!confirm('Are you sure you want to submit this RFQ?')) return;

            try {
                await api.client.post(`/vendor/rfqs/${rfqId}/submit`);
                alert('RFQ submitted successfully!');
                await this.loadRfqs();
                await this.loadStats();
            } catch (error) {
                console.error('Failed to submit RFQ:', error);
                alert('Failed to submit RFQ. Please try again.');
            }
        },

        isDeadlineSoon(deadline) {
            if (!deadline) return false;
            const days = Math.ceil((new Date(deadline) - new Date()) / (1000 * 60 * 60 * 24));
            return days <= 3 && days >= 0;
        },

        getStatusClass(status) {
            const classes = {
                'draft': 'bg-gray-100 text-gray-800',
                'submitted': 'bg-yellow-100 text-yellow-800',
                'quoted': 'bg-blue-100 text-blue-800',
                'accepted': 'bg-green-100 text-green-800',
                'rejected': 'bg-red-100 text-red-800',
                'expired': 'bg-gray-100 text-gray-600'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }
    };
}
</script>
@endpush

@extends('layouts.app')

@section('title', 'RFQ Details')

@section('sidebar')
    @include('vendor.partials.sidebar')
@endsection

@section('content')
<div x-data="rfqDetailData({{ $id }})" x-init="init()">
    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-20">
        <svg class="animate-spin h-12 w-12 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    <!-- RFQ Content -->
    <div x-show="!loading && rfq" class="max-w-7xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="mb-6 text-sm">
            <ol class="flex items-center space-x-2 text-gray-600">
                <li><a href="/vendor/rfqs" class="hover:text-blue-600">RFQs</a></li>
                <li><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg></li>
                <li class="text-gray-900 font-medium">RFQ #<span x-text="rfq?.id"></span></li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2" x-text="rfq?.title"></h1>
                    <p class="text-gray-600">Created on <span x-text="formatDate(rfq?.created_at)"></span></p>
                </div>
                <div class="mt-4 md:mt-0">
                    <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold" :class="getStatusClass(rfq?.status)" x-text="rfq?.status?.toUpperCase()"></span>
                </div>
            </div>

            <div class="border-t pt-4">
                <p class="text-gray-700" x-text="rfq?.description"></p>
            </div>

            <!-- Deadline Warning -->
            <div x-show="rfq?.deadline && isDeadlineNear(rfq?.deadline)" class="mt-4 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-orange-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-orange-800">
                        <strong>Deadline approaching:</strong> <span x-text="formatDate(rfq?.deadline)"></span>
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex flex-wrap gap-3">
                <button
                    x-show="rfq?.status === 'draft'"
                    @click="submitRfq()"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Submit RFQ
                </button>
                <button
                    x-show="rfq?.status === 'draft'"
                    @click="showEditModal = true"
                    class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                >
                    Edit RFQ
                </button>
                <button
                    x-show="rfq?.status === 'pending' || rfq?.status === 'draft'"
                    @click="cancelRfq()"
                    class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                >
                    Cancel RFQ
                </button>
                <button
                    @click="duplicateRfq()"
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                >
                    Duplicate
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Items & Responses -->
            <div class="lg:col-span-2 space-y-6">
                <!-- RFQ Items -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Requested Items</h2>

                    <div x-show="rfq?.items && rfq.items.length > 0" class="space-y-3">
                        <template x-for="item in rfq?.items || []" :key="item.id">
                            <div class="border rounded-lg p-4 hover:bg-gray-50">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900" x-text="item.product?.name || item.description"></h3>
                                        <p class="text-sm text-gray-600 mt-1" x-text="item.description"></p>
                                        <div class="mt-2 flex items-center space-x-4 text-sm text-gray-600">
                                            <span>Quantity: <strong x-text="item.quantity"></strong></span>
                                            <span x-show="item.target_price">Target Price: <strong x-text="formatCurrency(item.target_price)"></strong></span>
                                        </div>
                                    </div>
                                    <div x-show="item.product" class="ml-4">
                                        <a :href="`/vendor/products/${item.product_id}`" class="text-blue-600 hover:underline text-sm">View Product</a>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="!rfq?.items || rfq.items.length === 0" class="text-center py-8 text-gray-500">
                        No items requested
                    </div>
                </div>

                <!-- Vendor Responses -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Vendor Responses</h2>

                    <div x-show="responses.length > 0" class="space-y-4">
                        <template x-for="response in responses" :key="response.id">
                            <div class="border rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h3 class="font-semibold text-gray-900" x-text="response.vendor_name"></h3>
                                        <p class="text-sm text-gray-600" x-text="formatDate(response.submitted_at)"></p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold" :class="getResponseStatusClass(response.status)" x-text="response.status"></span>
                                </div>

                                <div class="bg-gray-50 rounded p-3 mb-3">
                                    <p class="text-sm text-gray-700" x-text="response.message"></p>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="text-lg font-bold text-gray-900">
                                        Total: <span x-text="formatCurrency(response.total_price)"></span>
                                    </div>
                                    <div class="flex gap-2">
                                        <button
                                            x-show="response.status === 'pending'"
                                            @click="acceptResponse(response.id)"
                                            class="px-4 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700"
                                        >
                                            Accept
                                        </button>
                                        <button
                                            x-show="response.status === 'pending'"
                                            @click="rejectResponse(response.id)"
                                            class="px-4 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700"
                                        >
                                            Reject
                                        </button>
                                        <button
                                            @click="viewResponseDetails(response.id)"
                                            class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700"
                                        >
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="responses.length === 0" class="text-center py-8">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p class="text-gray-500">No responses yet</p>
                        <p class="text-sm text-gray-400 mt-1">Vendors will respond to your RFQ soon</p>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Activity Timeline</h2>
                    <div class="space-y-4">
                        <template x-for="(activity, index) in timeline" :key="index">
                            <div class="flex">
                                <div class="flex flex-col items-center mr-4">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="activity.color">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <circle cx="10" cy="10" r="3"/>
                                        </svg>
                                    </div>
                                    <div x-show="index < timeline.length - 1" class="w-0.5 h-full bg-gray-300 mt-2"></div>
                                </div>
                                <div class="pb-8">
                                    <p class="text-sm font-semibold text-gray-900" x-text="activity.title"></p>
                                    <p class="text-xs text-gray-600" x-text="activity.date"></p>
                                    <p class="text-sm text-gray-700 mt-1" x-text="activity.description"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Right Column - Details -->
            <div class="lg:col-span-1 space-y-6">
                <!-- RFQ Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">RFQ Information</h2>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-600">RFQ ID</p>
                            <p class="font-semibold" x-text="'#' + rfq?.id"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Status</p>
                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold mt-1" :class="getStatusClass(rfq?.status)" x-text="rfq?.status"></span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Created</p>
                            <p class="font-semibold" x-text="formatDate(rfq?.created_at)"></p>
                        </div>
                        <div x-show="rfq?.deadline">
                            <p class="text-sm text-gray-600">Deadline</p>
                            <p class="font-semibold" x-text="formatDate(rfq?.deadline)"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Items</p>
                            <p class="font-semibold" x-text="rfq?.items?.length || 0"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Responses Received</p>
                            <p class="font-semibold" x-text="responses.length"></p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-900 mb-3">Quick Actions</h3>
                    <div class="space-y-2">
                        <button class="w-full text-left px-3 py-2 bg-white rounded hover:bg-blue-100 text-sm transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                            </svg>
                            Add Note
                        </button>
                        <button class="w-full text-left px-3 py-2 bg-white rounded hover:bg-blue-100 text-sm transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            Send Reminder
                        </button>
                        <button @click="downloadRfq()" class="w-full text-left px-3 py-2 bg-white rounded hover:bg-blue-100 text-sm transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Download PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit RFQ Modal -->
    <div x-show="showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-cloak>
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900">Edit RFQ</h3>
                <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="updateRfq()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                        <input type="text" x-model="editForm.title" class="w-full px-4 py-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea x-model="editForm.description" rows="4" class="w-full px-4 py-2 border rounded-lg"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Deadline</label>
                        <input type="date" x-model="editForm.deadline" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function rfqDetailData(rfqId) {
    return {
        rfq: null,
        responses: [],
        timeline: [],
        loading: true,
        showEditModal: false,
        editForm: {
            title: '',
            description: '',
            deadline: ''
        },

        async init() {
            await this.loadRfq();
            await this.loadResponses();
            this.buildTimeline();
        },

        async loadRfq() {
            this.loading = true;
            try {
                const data = await api.getRfq(rfqId);
                this.rfq = data;
                this.editForm = {
                    title: data.title,
                    description: data.description,
                    deadline: data.deadline ? data.deadline.split('T')[0] : ''
                };
            } catch (error) {
                console.error('Failed to load RFQ:', error);
                alert('Failed to load RFQ details');
            } finally {
                this.loading = false;
            }
        },

        async loadResponses() {
            try {
                const data = await api.client.get(`/vendor/rfqs/${rfqId}/responses`);
                this.responses = data.data?.data || [];
            } catch (error) {
                console.error('Failed to load responses:', error);
                this.responses = [];
            }
        },

        buildTimeline() {
            this.timeline = [
                {
                    title: 'RFQ Created',
                    date: this.formatDate(this.rfq?.created_at),
                    description: 'RFQ was created and saved as draft',
                    color: 'bg-gray-500'
                }
            ];

            if (this.rfq?.status !== 'draft') {
                this.timeline.push({
                    title: 'RFQ Submitted',
                    date: this.formatDate(this.rfq?.submitted_at || this.rfq?.updated_at),
                    description: 'RFQ was submitted to vendors',
                    color: 'bg-blue-500'
                });
            }

            if (this.responses.length > 0) {
                this.timeline.push({
                    title: 'Responses Received',
                    date: this.formatDate(this.responses[0]?.submitted_at),
                    description: `Received ${this.responses.length} response(s) from vendors`,
                    color: 'bg-green-500'
                });
            }

            if (this.rfq?.status === 'completed') {
                this.timeline.push({
                    title: 'RFQ Completed',
                    date: this.formatDate(this.rfq?.completed_at || this.rfq?.updated_at),
                    description: 'RFQ was completed successfully',
                    color: 'bg-green-600'
                });
            }

            if (this.rfq?.status === 'cancelled') {
                this.timeline.push({
                    title: 'RFQ Cancelled',
                    date: this.formatDate(this.rfq?.updated_at),
                    description: 'RFQ was cancelled',
                    color: 'bg-red-500'
                });
            }
        },

        async submitRfq() {
            if (!confirm('Submit this RFQ to vendors?')) return;

            try {
                await api.client.post(`/vendor/rfqs/${rfqId}/submit`);
                alert('RFQ submitted successfully!');
                await this.loadRfq();
                this.buildTimeline();
            } catch (error) {
                console.error('Failed to submit RFQ:', error);
                alert('Failed to submit RFQ. Please try again.');
            }
        },

        async updateRfq() {
            try {
                await api.updateRfq(rfqId, this.editForm);
                this.showEditModal = false;
                alert('RFQ updated successfully!');
                await this.loadRfq();
            } catch (error) {
                console.error('Failed to update RFQ:', error);
                alert('Failed to update RFQ. Please try again.');
            }
        },

        async cancelRfq() {
            if (!confirm('Are you sure you want to cancel this RFQ?')) return;

            try {
                await api.deleteRfq(rfqId);
                alert('RFQ cancelled successfully!');
                window.location.href = '/vendor/rfqs';
            } catch (error) {
                console.error('Failed to cancel RFQ:', error);
                alert('Failed to cancel RFQ. Please try again.');
            }
        },

        async duplicateRfq() {
            try {
                const newRfq = await api.client.post(`/vendor/rfqs/${rfqId}/duplicate`);
                alert('RFQ duplicated successfully!');
                window.location.href = `/vendor/rfqs/${newRfq.data.id}`;
            } catch (error) {
                console.error('Failed to duplicate RFQ:', error);
                alert('Failed to duplicate RFQ. Please try again.');
            }
        },

        async acceptResponse(responseId) {
            if (!confirm('Accept this response and create an order?')) return;

            try {
                await api.client.post(`/vendor/rfqs/responses/${responseId}/accept`);
                alert('Response accepted! An order has been created.');
                await this.loadResponses();
                this.buildTimeline();
            } catch (error) {
                console.error('Failed to accept response:', error);
                alert('Failed to accept response. Please try again.');
            }
        },

        async rejectResponse(responseId) {
            if (!confirm('Reject this response?')) return;

            try {
                await api.client.post(`/vendor/rfqs/responses/${responseId}/reject`);
                alert('Response rejected.');
                await this.loadResponses();
            } catch (error) {
                console.error('Failed to reject response:', error);
                alert('Failed to reject response. Please try again.');
            }
        },

        viewResponseDetails(responseId) {
            // In a real implementation, this would open a modal with full response details
            alert('Response details would be shown here');
        },

        downloadRfq() {
            window.open(`/api/vendor/rfqs/${rfqId}/pdf`, '_blank');
        },

        isDeadlineNear(deadline) {
            if (!deadline) return false;
            const deadlineDate = new Date(deadline);
            const now = new Date();
            const diffDays = Math.ceil((deadlineDate - now) / (1000 * 60 * 60 * 24));
            return diffDays <= 7 && diffDays > 0;
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'MAD'
            }).format(amount || 0);
        },

        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
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
        },

        getResponseStatusClass(status) {
            const classes = {
                pending: 'bg-yellow-100 text-yellow-800',
                accepted: 'bg-green-100 text-green-800',
                rejected: 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }
    };
}
</script>
@endpush

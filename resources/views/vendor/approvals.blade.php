@extends('layouts.app')

@section('title', 'Approvals')

@section('sidebar')
    @include('vendor.partials.sidebar')
@endsection

@section('content')
<div x-data="approvalsData()" x-init="init()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Approval Workflows</h1>
        <p class="text-gray-600 mt-1">Manage approval requests and workflows</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Pending Approvals</p>
            <p class="text-3xl font-bold text-yellow-600" x-text="stats.pending || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Approved Today</p>
            <p class="text-3xl font-bold text-green-600" x-text="stats.approved_today || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Rejected</p>
            <p class="text-3xl font-bold text-red-600" x-text="stats.rejected || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Avg Response Time</p>
            <p class="text-3xl font-bold text-blue-600" x-text="(stats.avg_response_time || 0) + 'h'"></p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button
                @click="activeTab = 'pending'"
                :class="activeTab === 'pending' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="py-4 px-1 border-b-2 font-medium text-sm"
            >
                Pending Approvals
                <span x-show="stats.pending > 0" class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs" x-text="stats.pending"></span>
            </button>
            <button
                @click="activeTab = 'history'"
                :class="activeTab === 'history' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="py-4 px-1 border-b-2 font-medium text-sm"
            >
                Approval History
            </button>
            <button
                @click="activeTab = 'workflows'"
                :class="activeTab === 'workflows' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="py-4 px-1 border-b-2 font-medium text-sm"
            >
                Workflows
            </button>
        </nav>
    </div>

    <!-- Pending Approvals -->
    <div x-show="activeTab === 'pending'">
        <div class="space-y-4">
            <template x-for="approval in pendingApprovals" :key="approval.id">
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full mr-3">
                                    PENDING APPROVAL
                                </span>
                                <span class="text-sm text-gray-600" x-text="'Request #' + approval.id"></span>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2" x-text="approval.subject_type + ' Approval'"></h3>
                            <p class="text-gray-600 mb-4" x-text="approval.metadata?.description || 'No description'"></p>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <p class="text-xs text-gray-500">Requested By</p>
                                    <p class="text-sm font-medium text-gray-900" x-text="approval.requester?.name || 'N/A'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Created</p>
                                    <p class="text-sm font-medium text-gray-900" x-text="new Date(approval.created_at).toLocaleDateString()"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Workflow</p>
                                    <p class="text-sm font-medium text-gray-900" x-text="approval.workflow?.name || 'Default'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Current Step</p>
                                    <p class="text-sm font-medium text-gray-900">
                                        <span x-text="approval.current_step + 1"></span> / <span x-text="approval.workflow?.total_steps || 1"></span>
                                    </p>
                                </div>
                            </div>

                            <!-- Approval Progress -->
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-xs text-gray-500">Approval Progress</p>
                                    <p class="text-xs text-gray-600" x-text="Math.round(((approval.current_step + 1) / (approval.workflow?.total_steps || 1)) * 100) + '%'"></p>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div
                                        class="bg-blue-600 h-2 rounded-full transition-all"
                                        :style="`width: ${((approval.current_step + 1) / (approval.workflow?.total_steps || 1)) * 100}%`"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-3 pt-4 border-t">
                        <button
                            @click="openApproveModal(approval)"
                            class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium"
                        >
                            ✓ Approve
                        </button>
                        <button
                            @click="openRejectModal(approval)"
                            class="flex-1 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium"
                        >
                            ✗ Reject
                        </button>
                        <button
                            @click="viewDetails(approval)"
                            class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium"
                        >
                            View Details
                        </button>
                    </div>
                </div>
            </template>

            <div x-show="pendingApprovals.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">All caught up!</h3>
                <p class="text-gray-600">No pending approvals at the moment</p>
            </div>
        </div>
    </div>

    <!-- History -->
    <div x-show="activeTab === 'history'">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Request ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="approval in allApprovals" :key="approval.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900" x-text="'#' + approval.id"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="approval.subject_type"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="approval.requester?.name || 'N/A'"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="new Date(approval.created_at).toLocaleDateString()"></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(approval.status)" x-text="approval.status"></span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button @click="viewDetails(approval)" class="text-blue-600 hover:underline">View</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Workflows -->
    <div x-show="activeTab === 'workflows'">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <template x-for="workflow in workflows" :key="workflow.id">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900" x-text="workflow.name"></h3>
                            <p class="text-sm text-gray-600 mt-1" x-text="workflow.description"></p>
                        </div>
                        <span class="px-3 py-1 text-xs rounded-full" :class="workflow.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                            <span x-text="workflow.is_active ? 'Active' : 'Inactive'"></span>
                        </span>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                            <span x-text="workflow.total_steps + ' approval steps'"></span>
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                            </svg>
                            <span x-text="'Type: ' + workflow.entity_type"></span>
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span x-text="workflow.auto_approve_after_hours ? workflow.auto_approve_after_hours + 'h auto-approve' : 'No auto-approve'"></span>
                        </div>
                    </div>
                </div>
            </template>

            <div x-show="workflows.length === 0" class="col-span-2 text-center py-12 bg-white rounded-lg shadow">
                <p class="text-gray-600">No workflows configured</p>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div
        x-show="showApproveModal"
        x-transition
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        @click.self="showApproveModal = false"
    >
        <div class="relative top-20 mx-auto p-8 border w-full max-w-md shadow-lg rounded-lg bg-white">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Approve Request</h3>
            <form @submit.prevent="approveRequest()">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Comments (Optional)</label>
                    <textarea
                        x-model="approvalComments"
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                        placeholder="Add any comments..."
                    ></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button
                        type="button"
                        @click="showApproveModal = false"
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                    >
                        Approve
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div
        x-show="showRejectModal"
        x-transition
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        @click.self="showRejectModal = false"
    >
        <div class="relative top-20 mx-auto p-8 border w-full max-w-md shadow-lg rounded-lg bg-white">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Reject Request</h3>
            <form @submit.prevent="rejectRequest()">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reason (Required)</label>
                    <textarea
                        x-model="rejectionReason"
                        rows="3"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500"
                        placeholder="Please provide a reason for rejection..."
                    ></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button
                        type="button"
                        @click="showRejectModal = false"
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                    >
                        Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function approvalsData() {
    return {
        activeTab: 'pending',
        pendingApprovals: [],
        allApprovals: [],
        workflows: [],
        stats: {},
        showApproveModal: false,
        showRejectModal: false,
        selectedApproval: null,
        approvalComments: '',
        rejectionReason: '',

        async init() {
            await this.loadPendingApprovals();
            await this.loadAllApprovals();
            await this.loadWorkflows();
            await this.loadStats();
        },

        async loadPendingApprovals() {
            try {
                const data = await api.getPendingApprovals();
                this.pendingApprovals = data.data || [];
            } catch (error) {
                console.error('Failed to load pending approvals:', error);
                this.pendingApprovals = [];
            }
        },

        async loadAllApprovals() {
            try {
                const data = await api.client.get('/vendor/approvals/requests');
                this.allApprovals = data.data.data || [];
            } catch (error) {
                console.error('Failed to load approvals:', error);
                this.allApprovals = [];
            }
        },

        async loadWorkflows() {
            try {
                const data = await api.getApprovalWorkflows();
                this.workflows = data.data || [];
            } catch (error) {
                console.error('Failed to load workflows:', error);
                this.workflows = [];
            }
        },

        async loadStats() {
            try {
                const data = await api.client.get('/vendor/approvals/stats');
                this.stats = data.data || {};
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        openApproveModal(approval) {
            this.selectedApproval = approval;
            this.approvalComments = '';
            this.showApproveModal = true;
        },

        openRejectModal(approval) {
            this.selectedApproval = approval;
            this.rejectionReason = '';
            this.showRejectModal = true;
        },

        async approveRequest() {
            try {
                await api.approveRequest(this.selectedApproval.id, this.approvalComments);
                this.showApproveModal = false;
                alert('Request approved successfully!');
                await this.loadPendingApprovals();
                await this.loadAllApprovals();
                await this.loadStats();
            } catch (error) {
                console.error('Failed to approve:', error);
                alert('Failed to approve request. Please try again.');
            }
        },

        async rejectRequest() {
            try {
                await api.rejectRequest(this.selectedApproval.id, this.rejectionReason);
                this.showRejectModal = false;
                alert('Request rejected successfully!');
                await this.loadPendingApprovals();
                await this.loadAllApprovals();
                await this.loadStats();
            } catch (error) {
                console.error('Failed to reject:', error);
                alert('Failed to reject request. Please try again.');
            }
        },

        viewDetails(approval) {
            // Navigate to details page or show modal
            alert('Details view - Feature coming soon');
        },

        getStatusClass(status) {
            const classes = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'approved': 'bg-green-100 text-green-800',
                'rejected': 'bg-red-100 text-red-800',
                'expired': 'bg-gray-100 text-gray-600'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }
    };
}
</script>
@endpush

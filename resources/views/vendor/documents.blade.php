@extends('layouts.app')

@section('title', 'Documents')

@section('sidebar')
    @include('vendor.partials.sidebar')
@endsection

@section('content')
<div x-data="documentsData()" x-init="init()">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Document Management</h1>
            <p class="text-gray-600 mt-1">Upload and manage your business documents</p>
        </div>
        <button
            @click="showUploadModal = true"
            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
        >
            + Upload Document
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Total Documents</p>
            <p class="text-2xl font-bold text-gray-900" x-text="stats.total || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Expiring Soon</p>
            <p class="text-2xl font-bold text-orange-600" x-text="stats.expiring || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Shared Documents</p>
            <p class="text-2xl font-bold text-blue-600" x-text="stats.shared || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Storage Used</p>
            <p class="text-2xl font-bold text-purple-600" x-text="formatSize(stats.storage_used || 0)"></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <select
                    x-model="typeFilter"
                    @change="loadDocuments()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Types</option>
                    <option value="contract">Contract</option>
                    <option value="invoice">Invoice</option>
                    <option value="certificate">Certificate</option>
                    <option value="report">Report</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <select
                    x-model="statusFilter"
                    @change="loadDocuments()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="expiring">Expiring Soon</option>
                    <option value="expired">Expired</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
            <div>
                <input
                    type="text"
                    x-model="searchQuery"
                    @input.debounce="loadDocuments()"
                    placeholder="Search documents..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
            </div>
            <div>
                <select
                    x-model="sortBy"
                    @change="loadDocuments()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="-created_at">Newest First</option>
                    <option value="created_at">Oldest First</option>
                    <option value="name">Name (A-Z)</option>
                    <option value="-name">Name (Z-A)</option>
                    <option value="expiry_date">Expiry Date</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Documents Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="doc in documents" :key="doc.id">
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <!-- Document Icon -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 rounded-lg" :class="getTypeColor(doc.document_type)">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <span
                            x-show="isExpiring(doc.expiry_date)"
                            class="px-2 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded-full"
                        >
                            Expiring Soon
                        </span>
                    </div>

                    <!-- Document Info -->
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1" x-text="doc.name"></h3>
                        <p class="text-sm text-gray-600 mb-2" x-text="doc.document_type"></p>
                        <p class="text-xs text-gray-500" x-text="formatSize(doc.file_size)"></p>
                    </div>

                    <!-- Metadata -->
                    <div class="space-y-2 mb-4 text-sm">
                        <div class="flex items-center text-gray-600">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                            </svg>
                            <span x-text="'Uploaded: ' + new Date(doc.created_at).toLocaleDateString()"></span>
                        </div>
                        <div x-show="doc.expiry_date" class="flex items-center" :class="isExpired(doc.expiry_date) ? 'text-red-600' : 'text-gray-600'">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span x-text="'Expires: ' + (doc.expiry_date ? new Date(doc.expiry_date).toLocaleDateString() : 'N/A')"></span>
                        </div>
                        <div x-show="doc.shares_count > 0" class="flex items-center text-blue-600">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z"/>
                            </svg>
                            <span x-text="'Shared with ' + doc.shares_count + ' users'"></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-2 pt-4 border-t">
                        <button
                            @click="downloadDocument(doc.id)"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700"
                        >
                            Download
                        </button>
                        <button
                            @click="viewDocument(doc)"
                            class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <button
                            @click="archiveDocument(doc.id)"
                            class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"/>
                                <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <div x-show="!loading && documents.length === 0" class="col-span-3 text-center py-12 bg-white rounded-lg shadow">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No documents found</h3>
            <p class="text-gray-600 mb-4">Upload your first document to get started</p>
            <button
                @click="showUploadModal = true"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
                Upload Document
            </button>
        </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="text-center py-12">
        <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    <!-- Upload Modal -->
    <div
        x-show="showUploadModal"
        x-transition
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        @click.self="showUploadModal = false"
    >
        <div class="relative top-20 mx-auto p-8 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Upload Document</h3>
                <button @click="showUploadModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="uploadDocument()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Document Name</label>
                        <input
                            type="text"
                            x-model="uploadForm.name"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="Enter document name"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                        <select
                            x-model="uploadForm.document_type"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Select type</option>
                            <option value="contract">Contract</option>
                            <option value="invoice">Invoice</option>
                            <option value="certificate">Certificate</option>
                            <option value="report">Report</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">File</label>
                        <input
                            type="file"
                            @change="uploadForm.file = $event.target.files[0]"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                        >
                        <p class="text-xs text-gray-500 mt-1">Accepted: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (Max 10MB)</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date (Optional)</label>
                        <input
                            type="date"
                            x-model="uploadForm.expiry_date"
                            :min="new Date().toISOString().split('T')[0]"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                        <textarea
                            x-model="uploadForm.description"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="Add description..."
                        ></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button
                            type="button"
                            @click="showUploadModal = false"
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="uploading"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                        >
                            <span x-show="!uploading">Upload</span>
                            <span x-show="uploading">Uploading...</span>
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
function documentsData() {
    return {
        documents: [],
        stats: {},
        loading: false,
        uploading: false,
        showUploadModal: false,
        typeFilter: '',
        statusFilter: '',
        searchQuery: '',
        sortBy: '-created_at',
        uploadForm: {
            name: '',
            document_type: '',
            file: null,
            expiry_date: '',
            description: ''
        },

        async init() {
            await this.loadDocuments();
            await this.loadStats();
        },

        async loadDocuments() {
            this.loading = true;
            try {
                const params = { sort: this.sortBy };
                if (this.typeFilter) params.document_type = this.typeFilter;
                if (this.statusFilter) params.status = this.statusFilter;
                if (this.searchQuery) params.search = this.searchQuery;

                const data = await api.getDocuments(params);
                this.documents = data.data || [];
            } catch (error) {
                console.error('Failed to load documents:', error);
                this.documents = [];
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const data = await api.client.get('/vendor/documents/stats');
                this.stats = data.data || {};
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async uploadDocument() {
            this.uploading = true;
            try {
                const formData = new FormData();
                formData.append('name', this.uploadForm.name);
                formData.append('document_type', this.uploadForm.document_type);
                formData.append('file', this.uploadForm.file);
                if (this.uploadForm.expiry_date) formData.append('expiry_date', this.uploadForm.expiry_date);
                if (this.uploadForm.description) formData.append('description', this.uploadForm.description);

                await api.uploadDocument(formData);
                this.showUploadModal = false;
                this.uploadForm = { name: '', document_type: '', file: null, expiry_date: '', description: '' };
                alert('Document uploaded successfully!');
                await this.loadDocuments();
                await this.loadStats();
            } catch (error) {
                console.error('Failed to upload document:', error);
                alert('Failed to upload document. Please try again.');
            } finally {
                this.uploading = false;
            }
        },

        async downloadDocument(docId) {
            try {
                const blob = await api.downloadDocument(docId);
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'document';
                a.click();
            } catch (error) {
                console.error('Failed to download:', error);
                alert('Failed to download document.');
            }
        },

        async archiveDocument(docId) {
            if (!confirm('Are you sure you want to archive this document?')) return;

            try {
                await api.client.post(`/vendor/documents/${docId}/archive`);
                alert('Document archived successfully!');
                await this.loadDocuments();
            } catch (error) {
                console.error('Failed to archive:', error);
                alert('Failed to archive document.');
            }
        },

        viewDocument(doc) {
            alert('Document viewer - Feature coming soon');
        },

        isExpiring(expiryDate) {
            if (!expiryDate) return false;
            const days = Math.ceil((new Date(expiryDate) - new Date()) / (1000 * 60 * 60 * 24));
            return days <= 30 && days >= 0;
        },

        isExpired(expiryDate) {
            if (!expiryDate) return false;
            return new Date(expiryDate) < new Date();
        },

        getTypeColor(type) {
            const colors = {
                'contract': 'bg-blue-100 text-blue-600',
                'invoice': 'bg-green-100 text-green-600',
                'certificate': 'bg-purple-100 text-purple-600',
                'report': 'bg-orange-100 text-orange-600',
                'other': 'bg-gray-100 text-gray-600'
            };
            return colors[type] || 'bg-gray-100 text-gray-600';
        },

        formatSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    };
}
</script>
@endpush

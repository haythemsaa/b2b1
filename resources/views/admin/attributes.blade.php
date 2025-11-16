@extends('layouts.app')

@section('title', 'Product Attributes')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div x-data="attributesData()" x-init="init()">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Product Attributes</h1>
            <p class="text-gray-600 mt-1">Define custom attributes for product categories</p>
        </div>
        <button @click="showCreateModal = true" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
            <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
            </svg>
            New Attribute
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow p-6 text-white">
            <p class="text-sm opacity-90 mb-1">Total Attributes</p>
            <p class="text-3xl font-bold" x-text="stats.total || 0"></p>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow p-6 text-white">
            <p class="text-sm opacity-90 mb-1">Filterable</p>
            <p class="text-3xl font-bold" x-text="stats.filterable || 0"></p>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow p-6 text-white">
            <p class="text-sm opacity-90 mb-1">Variant</p>
            <p class="text-3xl font-bold" x-text="stats.variant || 0"></p>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow p-6 text-white">
            <p class="text-sm opacity-90 mb-1">Required</p>
            <p class="text-3xl font-bold" x-text="stats.required || 0"></p>
        </div>
        <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-lg shadow p-6 text-white">
            <p class="text-sm opacity-90 mb-1">Categories Using</p>
            <p class="text-3xl font-bold" x-text="stats.categories_count || 0"></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <select x-model="typeFilter" @change="loadAttributes()" class="w-full px-4 py-2 border rounded-lg">
                    <option value="">All Types</option>
                    <option value="text">Text</option>
                    <option value="number">Number</option>
                    <option value="select">Select</option>
                    <option value="multiselect">Multi-select</option>
                    <option value="color">Color</option>
                    <option value="boolean">Yes/No</option>
                </select>
            </div>
            <div>
                <input type="text" x-model="search" @input="debounceSearch()" placeholder="Search attributes..." class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div class="flex gap-2">
                <label class="flex items-center">
                    <input type="checkbox" x-model="filterFilterable" @change="loadAttributes()" class="mr-2">
                    <span class="text-sm">Filterable only</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" x-model="filterVariant" @change="loadAttributes()" class="mr-2">
                    <span class="text-sm">Variant only</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Attributes Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Options</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Properties</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categories</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="attr in attributes" :key="attr.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900" x-text="attr.name"></div>
                            <div class="text-xs text-gray-500" x-text="attr.slug"></div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full" :class="getTypeClass(attr.type)" x-text="attr.type"></span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <div x-show="attr.options && attr.options.length > 0">
                                <span x-text="attr.options.length + ' options'"></span>
                                <button @click="showOptions(attr)" class="text-blue-600 hover:underline ml-2">View</button>
                            </div>
                            <span x-show="!attr.options || attr.options.length === 0" class="text-gray-400">-</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                <span x-show="attr.is_filterable" class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Filterable</span>
                                <span x-show="attr.is_variant" class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded">Variant</span>
                                <span x-show="attr.is_required" class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded">Required</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span x-text="attr.categories_count || 0"></span> categories
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <button @click="editAttribute(attr)" class="text-blue-600 hover:underline mr-3">Edit</button>
                            <button @click="deleteAttribute(attr)" class="text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                </template>
                <tr x-show="!loading && attributes.length === 0">
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">No attributes found</td>
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

    <!-- Create/Edit Modal -->
    <div x-show="showCreateModal || showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-cloak>
        <div class="relative top-20 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-lg bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900" x-text="showCreateModal ? 'Create Attribute' : 'Edit Attribute'"></h3>
                <button @click="closeModals()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="saveAttribute()">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                            <input type="text" x-model="attributeForm.name" class="w-full px-4 py-2 border rounded-lg" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                            <select x-model="attributeForm.type" class="w-full px-4 py-2 border rounded-lg" required>
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="select">Select</option>
                                <option value="multiselect">Multi-select</option>
                                <option value="color">Color</option>
                                <option value="boolean">Yes/No</option>
                            </select>
                        </div>
                    </div>

                    <!-- Options (for select/multiselect) -->
                    <div x-show="['select', 'multiselect'].includes(attributeForm.type)">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Options *</label>
                        <div class="space-y-2">
                            <template x-for="(option, index) in attributeForm.options" :key="index">
                                <div class="flex gap-2">
                                    <input type="text" x-model="attributeForm.options[index]" class="flex-1 px-4 py-2 border rounded-lg" placeholder="Option value">
                                    <button type="button" @click="removeOption(index)" class="px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="addOption()" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200">
                                + Add Option
                            </button>
                        </div>
                    </div>

                    <!-- Properties -->
                    <div class="grid grid-cols-3 gap-4 pt-4 border-t">
                        <label class="flex items-center">
                            <input type="checkbox" x-model="attributeForm.is_filterable" class="mr-2">
                            <div>
                                <span class="text-sm font-medium">Filterable</span>
                                <p class="text-xs text-gray-500">Show in filters</p>
                            </div>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" x-model="attributeForm.is_variant" class="mr-2">
                            <div>
                                <span class="text-sm font-medium">Variant</span>
                                <p class="text-xs text-gray-500">Create variants</p>
                            </div>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" x-model="attributeForm.is_required" class="mr-2">
                            <div>
                                <span class="text-sm font-medium">Required</span>
                                <p class="text-xs text-gray-500">Mandatory field</p>
                            </div>
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                        <input type="number" x-model="attributeForm.order" class="w-full px-4 py-2 border rounded-lg" min="0">
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="closeModals()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        <span x-show="!saving">Save</span>
                        <span x-show="saving">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Options View Modal -->
    <div x-show="showOptionsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-cloak>
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-lg bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900">Attribute Options</h3>
                <button @click="showOptionsModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-2">
                <template x-for="(option, index) in viewingOptions" :key="index">
                    <div class="px-4 py-2 bg-gray-50 rounded" x-text="option"></div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function attributesData() {
    return {
        attributes: [],
        stats: {},
        loading: false,
        saving: false,
        showCreateModal: false,
        showEditModal: false,
        showOptionsModal: false,
        typeFilter: '',
        search: '',
        filterFilterable: false,
        filterVariant: false,
        viewingOptions: [],
        attributeForm: {
            id: null,
            name: '',
            type: 'text',
            options: [],
            is_filterable: true,
            is_required: false,
            is_variant: false,
            order: 0
        },

        async init() {
            await this.loadAttributes();
            await this.loadStats();
        },

        async loadAttributes() {
            this.loading = true;
            try {
                const params = {};
                if (this.typeFilter) params.type = this.typeFilter;
                if (this.search) params.search = this.search;
                if (this.filterFilterable) params.filterable = 1;
                if (this.filterVariant) params.variant = 1;

                const response = await api.client.get('/admin/attributes', { params });
                this.attributes = response.data.data || [];
            } catch (error) {
                console.error('Failed to load attributes:', error);
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const response = await api.client.get('/admin/attributes/stats');
                this.stats = response.data || {};
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async saveAttribute() {
            this.saving = true;
            try {
                // Clean up options if not select/multiselect
                const data = { ...this.attributeForm };
                if (!['select', 'multiselect'].includes(data.type)) {
                    data.options = [];
                }

                if (this.showEditModal) {
                    await api.client.put(`/admin/attributes/${data.id}`, data);
                } else {
                    await api.client.post('/admin/attributes', data);
                }

                this.closeModals();
                await this.loadAttributes();
                await this.loadStats();
                alert('Attribute saved successfully!');
            } catch (error) {
                console.error('Failed to save attribute:', error);
                alert('Failed to save attribute. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        editAttribute(attr) {
            this.attributeForm = {
                id: attr.id,
                name: attr.name,
                type: attr.type,
                options: attr.options || [],
                is_filterable: attr.is_filterable,
                is_required: attr.is_required,
                is_variant: attr.is_variant,
                order: attr.order || 0
            };
            this.showEditModal = true;
        },

        async deleteAttribute(attr) {
            if (!confirm(`Delete "${attr.name}"? This cannot be undone.`)) return;

            try {
                await api.client.delete(`/admin/attributes/${attr.id}`);
                await this.loadAttributes();
                await this.loadStats();
                alert('Attribute deleted successfully!');
            } catch (error) {
                console.error('Failed to delete attribute:', error);
                alert('Failed to delete attribute. It may be in use.');
            }
        },

        showOptions(attr) {
            this.viewingOptions = attr.options || [];
            this.showOptionsModal = true;
        },

        addOption() {
            this.attributeForm.options.push('');
        },

        removeOption(index) {
            this.attributeForm.options.splice(index, 1);
        },

        closeModals() {
            this.showCreateModal = false;
            this.showEditModal = false;
            this.attributeForm = {
                id: null,
                name: '',
                type: 'text',
                options: [],
                is_filterable: true,
                is_required: false,
                is_variant: false,
                order: 0
            };
        },

        debounceSearch: window.utils?.debounce(function() {
            this.loadAttributes();
        }, 500) || function() { this.loadAttributes(); },

        getTypeClass(type) {
            const classes = {
                text: 'bg-blue-100 text-blue-800',
                number: 'bg-green-100 text-green-800',
                select: 'bg-purple-100 text-purple-800',
                multiselect: 'bg-pink-100 text-pink-800',
                color: 'bg-orange-100 text-orange-800',
                boolean: 'bg-gray-100 text-gray-800'
            };
            return classes[type] || 'bg-gray-100 text-gray-800';
        }
    };
}
</script>
@endpush

@extends('layouts.app')

@section('title', 'Product Categories')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div x-data="categoriesData()" x-init="init()">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Product Categories</h1>
            <p class="text-gray-600 mt-1">Organize products with unlimited category hierarchy</p>
        </div>
        <button @click="showCreateModal = true" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
            <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
            </svg>
            New Category
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow p-6 text-white">
            <p class="text-sm opacity-90 mb-1">Total Categories</p>
            <p class="text-3xl font-bold" x-text="stats.total || 0"></p>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow p-6 text-white">
            <p class="text-sm opacity-90 mb-1">Root Categories</p>
            <p class="text-3xl font-bold" x-text="stats.root || 0"></p>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow p-6 text-white">
            <p class="text-sm opacity-90 mb-1">Active</p>
            <p class="text-3xl font-bold" x-text="stats.active || 0"></p>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow p-6 text-white">
            <p class="text-sm opacity-90 mb-1">Max Depth</p>
            <p class="text-3xl font-bold" x-text="stats.maxDepth || 0"></p>
        </div>
    </div>

    <!-- Category Tree -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Category Hierarchy</h2>

            <!-- Loading -->
            <div x-show="loading" class="py-12 text-center">
                <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <!-- Category Tree -->
            <div x-show="!loading" class="space-y-2">
                <template x-for="category in categories" :key="category.id">
                    <div x-data="{ expanded: true }">
                        <div class="category-item" :style="`padding-left: ${category.level * 20}px`">
                            <div class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                                <div class="flex items-center flex-1">
                                    <!-- Expand/Collapse -->
                                    <button
                                        x-show="category.children_count > 0"
                                        @click="expanded = !expanded"
                                        class="mr-2 text-gray-500 hover:text-gray-700"
                                    >
                                        <svg x-show="expanded" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                        <svg x-show="!expanded" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>

                                    <!-- Category Icon -->
                                    <div class="h-10 w-10 bg-blue-100 rounded flex items-center justify-center mr-3 flex-shrink-0">
                                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/>
                                        </svg>
                                    </div>

                                    <!-- Category Info -->
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-semibold text-gray-900" x-text="category.name"></h3>
                                            <span x-show="category.products_count > 0" class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded" x-text="category.products_count + ' products'"></span>
                                            <span x-show="category.children_count > 0" class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded" x-text="category.children_count + ' subcategories'"></span>
                                            <span x-show="!category.is_active" class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">Inactive</span>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1" x-text="category.breadcrumb"></p>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center gap-2">
                                        <button @click="showAttributes(category)" class="text-purple-600 hover:text-purple-700 px-3 py-1 text-sm rounded hover:bg-purple-50">
                                            <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM16 3a1 1 0 011 1v7.268a2 2 0 010 3.464V16a1 1 0 11-2 0v-1.268a2 2 0 010-3.464V4a1 1 0 011-1z"/>
                                            </svg>
                                            Attributes
                                        </button>
                                        <button @click="editCategory(category)" class="text-blue-600 hover:text-blue-700 px-3 py-1 text-sm rounded hover:bg-blue-50">
                                            Edit
                                        </button>
                                        <button @click="addSubcategory(category)" class="text-green-600 hover:text-green-700 px-3 py-1 text-sm rounded hover:bg-green-50">
                                            + Sub
                                        </button>
                                        <button @click="deleteCategory(category)" class="text-red-600 hover:text-red-700 px-3 py-1 text-sm rounded hover:bg-red-50">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div x-show="categories.length === 0" class="py-12 text-center text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p>No categories yet. Create your first category to get started.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div x-show="showCreateModal || showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-cloak>
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900" x-text="showCreateModal ? 'Create Category' : 'Edit Category'"></h3>
                <button @click="closeModals()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="saveCategory()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Parent Category</label>
                        <select x-model="categoryForm.parent_id" class="w-full px-4 py-2 border rounded-lg">
                            <option value="">None (Root Category)</option>
                            <template x-for="cat in allCategories" :key="cat.id">
                                <option :value="cat.id" x-text="cat.breadcrumb" :disabled="showEditModal && cat.id === categoryForm.id"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                        <input type="text" x-model="categoryForm.name" class="w-full px-4 py-2 border rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea x-model="categoryForm.description" rows="3" class="w-full px-4 py-2 border rounded-lg"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Order</label>
                            <input type="number" x-model="categoryForm.order" class="w-full px-4 py-2 border rounded-lg" min="0">
                        </div>

                        <div class="flex items-center pt-8">
                            <input type="checkbox" x-model="categoryForm.is_active" class="h-4 w-4 text-blue-600 rounded">
                            <label class="ml-2 text-sm text-gray-700">Active</label>
                        </div>
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
</div>
@endsection

@push('scripts')
<script>
function categoriesData() {
    return {
        categories: [],
        allCategories: [],
        stats: {},
        loading: false,
        saving: false,
        showCreateModal: false,
        showEditModal: false,
        categoryForm: {
            id: null,
            name: '',
            description: '',
            parent_id: '',
            order: 0,
            is_active: true
        },

        async init() {
            await this.loadCategories();
            await this.loadStats();
        },

        async loadCategories() {
            this.loading = true;
            try {
                const response = await api.client.get('/admin/categories');
                this.categories = response.data.data || [];
                this.allCategories = this.categories;
            } catch (error) {
                console.error('Failed to load categories:', error);
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const response = await api.client.get('/admin/categories/stats');
                this.stats = response.data || {};
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async saveCategory() {
            this.saving = true;
            try {
                if (this.showEditModal) {
                    await api.client.put(`/admin/categories/${this.categoryForm.id}`, this.categoryForm);
                } else {
                    await api.client.post('/admin/categories', this.categoryForm);
                }

                this.closeModals();
                await this.loadCategories();
                await this.loadStats();
                alert('Category saved successfully!');
            } catch (error) {
                console.error('Failed to save category:', error);
                alert('Failed to save category. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        editCategory(category) {
            this.categoryForm = {
                id: category.id,
                name: category.name,
                description: category.description || '',
                parent_id: category.parent_id || '',
                order: category.order || 0,
                is_active: category.is_active !== false
            };
            this.showEditModal = true;
        },

        addSubcategory(parent) {
            this.categoryForm = {
                id: null,
                name: '',
                description: '',
                parent_id: parent.id,
                order: 0,
                is_active: true
            };
            this.showCreateModal = true;
        },

        async deleteCategory(category) {
            if (!confirm(`Delete "${category.name}"? This will also delete all subcategories and cannot be undone.`)) return;

            try {
                await api.client.delete(`/admin/categories/${category.id}`);
                await this.loadCategories();
                await this.loadStats();
                alert('Category deleted successfully!');
            } catch (error) {
                console.error('Failed to delete category:', error);
                alert('Failed to delete category. It may have products associated with it.');
            }
        },

        showAttributes(category) {
            window.location.href = `/admin/categories/${category.id}/attributes`;
        },

        closeModals() {
            this.showCreateModal = false;
            this.showEditModal = false;
            this.categoryForm = {
                id: null,
                name: '',
                description: '',
                parent_id: '',
                order: 0,
                is_active: true
            };
        }
    };
}
</script>
@endpush

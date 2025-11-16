@extends('layouts.app')

@section('title', 'Products Management')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div x-data="productsData()" x-init="init()">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Products Management</h1>
            <p class="text-gray-600 mt-1">Manage product catalog and inventory</p>
        </div>
        <button
            @click="showCreateModal = true"
            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
        >
            + Add Product
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Total Products</p>
            <p class="text-2xl font-bold text-gray-900" x-text="stats.total || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">In Stock</p>
            <p class="text-2xl font-bold text-green-600" x-text="stats.in_stock || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Low Stock</p>
            <p class="text-2xl font-bold text-orange-600" x-text="stats.low_stock || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Out of Stock</p>
            <p class="text-2xl font-bold text-red-600" x-text="stats.out_of_stock || 0"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Categories</p>
            <p class="text-2xl font-bold text-blue-600" x-text="categories.length"></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <input
                    type="text"
                    x-model="searchQuery"
                    @input.debounce="loadProducts()"
                    placeholder="Search products..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
            </div>
            <div>
                <select
                    x-model="categoryFilter"
                    @change="loadProducts()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Categories</option>
                    <template x-for="cat in categories" :key="cat">
                        <option :value="cat" x-text="cat"></option>
                    </template>
                </select>
            </div>
            <div>
                <select
                    x-model="stockFilter"
                    @change="loadProducts()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Stock Levels</option>
                    <option value="in_stock">In Stock</option>
                    <option value="low_stock">Low Stock</option>
                    <option value="out_of_stock">Out of Stock</option>
                </select>
            </div>
            <div>
                <select
                    x-model="sortBy"
                    @change="loadProducts()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="name">Name (A-Z)</option>
                    <option value="-name">Name (Z-A)</option>
                    <option value="-price">Price (High-Low)</option>
                    <option value="price">Price (Low-High)</option>
                    <option value="-stock">Stock (High-Low)</option>
                    <option value="stock">Stock (Low-High)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">MOQ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="product in products" :key="product.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900" x-text="product.name"></div>
                                        <div class="text-sm text-gray-500 truncate max-w-xs" x-text="product.description"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-900" x-text="product.sku"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="product.category"></td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900" x-text="formatCurrency(product.price)"></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full" :class="getStockClass(product.stock)" x-text="product.stock"></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="product.moq"></td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <button @click="editProduct(product)" class="text-blue-600 hover:underline">Edit</button>
                                <button @click="adjustStock(product)" class="text-green-600 hover:underline">Stock</button>
                                <button @click="deleteProduct(product.id)" class="text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && products.length === 0">
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">No products found</td>
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

    <!-- Create/Edit Product Modal -->
    <div
        x-show="showCreateModal || showEditModal"
        x-transition
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        @click.self="closeModals()"
    >
        <div class="relative top-20 mx-auto p-8 border w-full max-w-3xl shadow-lg rounded-lg bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900" x-text="showEditModal ? 'Edit Product' : 'Create New Product'"></h3>
                <button @click="closeModals()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="showEditModal ? updateProduct() : createProduct()">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Product Name</label>
                            <input
                                type="text"
                                x-model="productForm.name"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                            <input
                                type="text"
                                x-model="productForm.sku"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea
                            x-model="productForm.description"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select
                                x-model="productForm.category"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">Select category</option>
                                <template x-for="cat in categories" :key="cat">
                                    <option :value="cat" x-text="cat"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Price</label>
                            <input
                                type="number"
                                x-model="productForm.price"
                                min="0"
                                step="0.01"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Stock</label>
                            <input
                                type="number"
                                x-model="productForm.stock"
                                min="0"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Order Qty (MOQ)</label>
                            <input
                                type="number"
                                x-model="productForm.moq"
                                min="1"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                            <input
                                type="number"
                                x-model="productForm.weight"
                                min="0"
                                step="0.01"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
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

    <!-- Stock Adjustment Modal -->
    <div
        x-show="showStockModal"
        x-transition
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        @click.self="showStockModal = false"
    >
        <div class="relative top-20 mx-auto p-8 border w-full max-w-md shadow-lg rounded-lg bg-white">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Adjust Stock</h3>
            <form @submit.prevent="saveStockAdjustment()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Stock</label>
                        <input
                            type="number"
                            :value="selectedProduct?.stock"
                            disabled
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Adjustment Type</label>
                        <select
                            x-model="stockAdjustment.type"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="add">Add Stock</option>
                            <option value="remove">Remove Stock</option>
                            <option value="set">Set Stock</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                        <input
                            type="number"
                            x-model="stockAdjustment.quantity"
                            min="0"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
                        <textarea
                            x-model="stockAdjustment.reason"
                            rows="2"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        ></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4">
                        <button
                            type="button"
                            @click="showStockModal = false"
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                        >
                            Adjust Stock
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
function productsData() {
    return {
        products: [],
        categories: [],
        stats: {},
        loading: false,
        saving: false,
        showCreateModal: false,
        showEditModal: false,
        showStockModal: false,
        searchQuery: '',
        categoryFilter: '',
        stockFilter: '',
        sortBy: 'name',
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 20
        },
        productForm: {
            id: null,
            name: '',
            sku: '',
            description: '',
            category: '',
            price: 0,
            stock: 0,
            moq: 1,
            weight: 0
        },
        selectedProduct: null,
        stockAdjustment: {
            type: 'add',
            quantity: 0,
            reason: ''
        },

        async init() {
            await this.loadCategories();
            await this.loadProducts();
            await this.loadStats();
        },

        async loadProducts() {
            this.loading = true;
            try {
                const params = {
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    sort: this.sortBy
                };

                if (this.searchQuery) params.search = this.searchQuery;
                if (this.categoryFilter) params.category = this.categoryFilter;
                if (this.stockFilter) params.stock_status = this.stockFilter;

                const data = await api.adminGetProducts(params);
                this.products = data.data || [];
                this.pagination = data.meta || this.pagination;
            } catch (error) {
                console.error('Failed to load products:', error);
                this.products = [];
            } finally {
                this.loading = false;
            }
        },

        async loadCategories() {
            try {
                const data = await api.client.get('/admin/products/categories');
                this.categories = data.data.data || [];
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        },

        async loadStats() {
            try {
                const data = await api.adminGetInventoryStats();
                this.stats = data || {};
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async createProduct() {
            this.saving = true;
            try {
                await api.adminCreateProduct(this.productForm);
                this.closeModals();
                alert('Product created successfully!');
                await this.loadProducts();
                await this.loadStats();
            } catch (error) {
                console.error('Failed to create product:', error);
                alert('Failed to create product. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        async updateProduct() {
            this.saving = true;
            try {
                await api.adminUpdateProduct(this.productForm.id, this.productForm);
                this.closeModals();
                alert('Product updated successfully!');
                await this.loadProducts();
            } catch (error) {
                console.error('Failed to update product:', error);
                alert('Failed to update product. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        editProduct(product) {
            this.productForm = {
                id: product.id,
                name: product.name,
                sku: product.sku,
                description: product.description || '',
                category: product.category,
                price: product.price,
                stock: product.stock,
                moq: product.moq,
                weight: product.weight || 0
            };
            this.showEditModal = true;
        },

        adjustStock(product) {
            this.selectedProduct = product;
            this.stockAdjustment = {
                type: 'add',
                quantity: 0,
                reason: ''
            };
            this.showStockModal = true;
        },

        async saveStockAdjustment() {
            try {
                await api.client.post(`/admin/products/${this.selectedProduct.id}/adjust-stock`, this.stockAdjustment);
                this.showStockModal = false;
                alert('Stock adjusted successfully!');
                await this.loadProducts();
                await this.loadStats();
            } catch (error) {
                console.error('Failed to adjust stock:', error);
                alert('Failed to adjust stock. Please try again.');
            }
        },

        async deleteProduct(productId) {
            if (!confirm('Are you sure you want to delete this product?')) return;

            try {
                await api.client.delete(`/admin/products/${productId}`);
                alert('Product deleted successfully!');
                await this.loadProducts();
                await this.loadStats();
            } catch (error) {
                console.error('Failed to delete product:', error);
                alert('Failed to delete product.');
            }
        },

        async loadPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.pagination.current_page = page;
            await this.loadProducts();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        closeModals() {
            this.showCreateModal = false;
            this.showEditModal = false;
            this.productForm = {
                id: null,
                name: '',
                sku: '',
                description: '',
                category: '',
                price: 0,
                stock: 0,
                moq: 1,
                weight: 0
            };
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'MAD'
            }).format(amount);
        },

        getStockClass(stock) {
            if (stock === 0) return 'bg-red-100 text-red-800';
            if (stock <= 10) return 'bg-orange-100 text-orange-800';
            return 'bg-green-100 text-green-800';
        }
    };
}
</script>
@endpush

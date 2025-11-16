@extends('layouts.app')

@section('title', 'Products')

@section('sidebar')
    @include('vendor.partials.sidebar')
@endsection

@section('content')
<div x-data="productsData()" x-init="init()">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Products</h1>
            <p class="text-gray-600 mt-1">Browse and search our product catalog</p>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <input
                    type="text"
                    x-model="search"
                    @input.debounce="loadProducts()"
                    placeholder="Search products..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
            </div>
            <div>
                <select
                    x-model="category"
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
                    x-model="sortBy"
                    @change="loadProducts()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="name">Name</option>
                    <option value="-price">Price: High to Low</option>
                    <option value="price">Price: Low to High</option>
                    <option value="-stock">Stock: High to Low</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <template x-for="product in products" :key="product.id">
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <span class="text-xs font-semibold text-blue-600 uppercase" x-text="product.category"></span>
                            <h3 class="text-lg font-semibold text-gray-900 mt-1" x-text="product.name"></h3>
                        </div>
                        <span
                            class="px-2 py-1 text-xs rounded-full"
                            :class="product.stock > 20 ? 'bg-green-100 text-green-800' : product.stock > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'"
                            x-text="`${product.stock} in stock`"
                        ></span>
                    </div>

                    <p class="text-sm text-gray-600 mb-4 line-clamp-2" x-text="product.description"></p>

                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-2xl font-bold text-gray-900" x-text="formatCurrency(product.price)"></p>
                            <p class="text-xs text-gray-600" x-text="`MOQ: ${product.moq}`"></p>
                        </div>
                    </div>

                    <div class="flex space-x-2">
                        <button
                            @click="viewProduct(product.id)"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"
                        >
                            View Details
                        </button>
                        <button
                            @click="addToCart(product)"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-12">
        <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600 mt-4">Loading products...</p>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && products.length === 0" class="text-center py-12">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No products found</h3>
        <p class="text-gray-600">Try adjusting your search or filter criteria</p>
    </div>

    <!-- Pagination -->
    <div x-show="pagination.last_page > 1" class="mt-6 flex items-center justify-center space-x-2">
        <button
            @click="loadPage(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            :class="pagination.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
            class="px-4 py-2 bg-white border border-gray-300 rounded-lg"
        >
            Previous
        </button>

        <template x-for="page in getPageNumbers()" :key="page">
            <button
                @click="loadPage(page)"
                :class="page === pagination.current_page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                class="px-4 py-2 border border-gray-300 rounded-lg"
                x-text="page"
            ></button>
        </template>

        <button
            @click="loadPage(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page"
            :class="pagination.current_page >= pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
            class="px-4 py-2 bg-white border border-gray-300 rounded-lg"
        >
            Next
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
function productsData() {
    return {
        products: [],
        categories: [],
        loading: false,
        search: '',
        category: '',
        sortBy: 'name',
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0
        },

        async init() {
            await this.loadCategories();
            await this.loadProducts();
        },

        async loadCategories() {
            try {
                const data = await api.getProducts({ per_page: 1000 });
                // Extract unique categories
                const uniqueCategories = [...new Set(data.data.map(p => p.category))];
                this.categories = uniqueCategories.filter(c => c);
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        },

        async loadProducts() {
            this.loading = true;
            try {
                const params = {
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    sort: this.sortBy
                };

                if (this.search) {
                    const data = await api.searchProducts(this.search, params);
                    this.products = data.data || [];
                    this.pagination = data.meta || this.pagination;
                } else {
                    if (this.category) params.category = this.category;
                    const data = await api.getProducts(params);
                    this.products = data.data || [];
                    this.pagination = data.meta || this.pagination;
                }
            } catch (error) {
                console.error('Failed to load products:', error);
                this.products = [];
            } finally {
                this.loading = false;
            }
        },

        async loadPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.pagination.current_page = page;
            await this.loadProducts();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        getPageNumbers() {
            const pages = [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;

            // Always show first page
            pages.push(1);

            // Show pages around current
            for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
                if (!pages.includes(i)) pages.push(i);
            }

            // Always show last page
            if (last > 1 && !pages.includes(last)) pages.push(last);

            return pages;
        },

        viewProduct(id) {
            window.location.href = `/vendor/products/${id}`;
        },

        addToCart(product) {
            // Get cart from localStorage
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');

            // Check if product already in cart
            const existingIndex = cart.findIndex(item => item.product_id === product.id);

            if (existingIndex >= 0) {
                cart[existingIndex].quantity += product.moq;
            } else {
                cart.push({
                    product_id: product.id,
                    name: product.name,
                    price: product.price,
                    quantity: product.moq,
                    moq: product.moq
                });
            }

            localStorage.setItem('cart', JSON.stringify(cart));

            // Show notification
            alert(`${product.name} added to cart!`);
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'MAD'
            }).format(amount);
        }
    };
}
</script>
@endpush

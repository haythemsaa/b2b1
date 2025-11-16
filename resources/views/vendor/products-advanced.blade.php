<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Catalog - Vendor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50">
    <div x-data="productCatalogData()" x-init="init()" class="min-h-screen">
        <!-- Header -->
        <div class="bg-white border-b sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Product Catalog</h1>
                        <p class="text-sm text-gray-600 mt-1">
                            <span x-text="pagination.total"></span> products available
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="showFilters = !showFilters"
                                class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center gap-2">
                            <span>Filters</span>
                            <span x-show="activeFiltersCount > 0"
                                  class="px-2 py-0.5 bg-blue-600 text-white text-xs rounded-full"
                                  x-text="activeFiltersCount"></span>
                        </button>
                        <select x-model="sortBy" @change="loadProducts()"
                                class="px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="created_desc">Newest</option>
                            <option value="created_asc">Oldest</option>
                            <option value="price_asc">Price: Low to High</option>
                            <option value="price_desc">Price: High to Low</option>
                            <option value="name_asc">Name: A-Z</option>
                            <option value="name_desc">Name: Z-A</option>
                        </select>
                        <select x-model="viewMode" class="px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="grid">Grid View</option>
                            <option value="list">List View</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex gap-6">
                <!-- Sidebar Filters -->
                <div x-show="showFilters" x-cloak
                     class="w-80 flex-shrink-0 bg-white rounded-lg shadow-sm p-6 h-fit sticky top-24">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold">Filters</h2>
                        <button @click="clearFilters()" class="text-sm text-blue-600 hover:text-blue-700">
                            Clear All
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" x-model="filters.search" @input.debounce.500ms="loadProducts()"
                               placeholder="Search products..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Categories -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select x-model="filters.category_id" @change="loadCategoryAttributes(); loadProducts();"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Categories</option>
                            <template x-for="category in categories" :key="category.id">
                                <option :value="category.id" x-text="category.breadcrumb || category.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Price Range -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                        <div class="flex gap-2">
                            <input type="number" x-model="filters.min_price" @change="loadProducts()"
                                   placeholder="Min" class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg">
                            <input type="number" x-model="filters.max_price" @change="loadProducts()"
                                   placeholder="Max" class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>

                    <!-- Stock Status -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Stock Status</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" x-model="filters.in_stock" @change="loadProducts()"
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-2 text-sm">In Stock Only</span>
                            </label>
                        </div>
                    </div>

                    <!-- Dynamic Attribute Filters -->
                    <div x-show="categoryAttributes.length > 0" x-cloak>
                        <h3 class="text-sm font-medium text-gray-700 mb-3 pt-3 border-t">Attributes</h3>
                        <div class="space-y-4">
                            <template x-for="attr in filterableAttributes" :key="attr.id">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2" x-text="attr.name"></label>

                                    <!-- Select Type -->
                                    <template x-if="attr.type === 'select'">
                                        <select x-model="filters.attributes[attr.slug]" @change="loadProducts()"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                            <option value="">All</option>
                                            <template x-for="option in attr.options" :key="option">
                                                <option :value="option" x-text="option"></option>
                                            </template>
                                        </select>
                                    </template>

                                    <!-- Multiselect Type (as checkboxes) -->
                                    <template x-if="attr.type === 'multiselect'">
                                        <div class="space-y-2 max-h-40 overflow-y-auto">
                                            <template x-for="option in attr.options" :key="option">
                                                <label class="flex items-center">
                                                    <input type="checkbox" :value="option"
                                                           x-model="filters.attributes[attr.slug]"
                                                           @change="loadProducts()"
                                                           class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                                    <span class="ml-2 text-sm" x-text="option"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Boolean Type -->
                                    <template x-if="attr.type === 'boolean'">
                                        <label class="flex items-center">
                                            <input type="checkbox" x-model="filters.attributes[attr.slug]"
                                                   @change="loadProducts()"
                                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                            <span class="ml-2 text-sm">Yes</span>
                                        </label>
                                    </template>

                                    <!-- Number Range -->
                                    <template x-if="attr.type === 'number'">
                                        <div class="flex gap-2">
                                            <input type="number"
                                                   x-model="filters.attributes[attr.slug + '_min']"
                                                   @change="loadProducts()"
                                                   placeholder="Min"
                                                   class="w-1/2 px-2 py-1 border border-gray-300 rounded text-sm">
                                            <input type="number"
                                                   x-model="filters.attributes[attr.slug + '_max']"
                                                   @change="loadProducts()"
                                                   placeholder="Max"
                                                   class="w-1/2 px-2 py-1 border border-gray-300 rounded text-sm">
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Product Grid/List -->
                <div class="flex-1">
                    <!-- Active Filters Pills -->
                    <div x-show="activeFiltersCount > 0" class="mb-4 flex flex-wrap gap-2">
                        <template x-for="(filter, key) in activeFiltersList" :key="key">
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                <span x-text="filter.label + ': ' + filter.value"></span>
                                <button @click="removeFilter(filter.key)" class="hover:text-blue-900">×</button>
                            </span>
                        </template>
                    </div>

                    <!-- Loading State -->
                    <div x-show="loading" class="text-center py-12">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent"></div>
                        <p class="mt-4 text-gray-600">Loading products...</p>
                    </div>

                    <!-- Grid View -->
                    <div x-show="!loading && viewMode === 'grid'"
                         class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <template x-for="product in products" :key="product.id">
                            <div @click="viewProduct(product)"
                                 class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer overflow-hidden">
                                <!-- Product Image -->
                                <div class="aspect-square bg-gray-100 flex items-center justify-center">
                                    <template x-if="product.image">
                                        <img :src="product.image" :alt="product.name" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!product.image">
                                        <span class="text-6xl">📦</span>
                                    </template>
                                </div>

                                <!-- Product Info -->
                                <div class="p-4">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="font-semibold text-gray-900 flex-1" x-text="product.name"></h3>
                                        <span x-show="product.type && product.type !== 'simple'"
                                              class="ml-2 px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs"
                                              x-text="product.type"></span>
                                    </div>

                                    <p class="text-sm text-gray-600 mb-3" x-text="product.sku"></p>

                                    <!-- Price -->
                                    <div class="mb-3">
                                        <template x-if="product.compare_price && product.compare_price > product.price">
                                            <div>
                                                <span class="text-lg font-bold text-gray-900">$<span x-text="product.price"></span></span>
                                                <span class="text-sm text-gray-500 line-through ml-2">$<span x-text="product.compare_price"></span></span>
                                                <span class="text-xs text-green-600 ml-2">
                                                    Save <span x-text="Math.round(((product.compare_price - product.price) / product.compare_price) * 100)"></span>%
                                                </span>
                                            </div>
                                        </template>
                                        <template x-if="!product.compare_price || product.compare_price <= product.price">
                                            <span class="text-lg font-bold text-gray-900">$<span x-text="product.price"></span></span>
                                        </template>
                                    </div>

                                    <!-- Stock Badge -->
                                    <div class="flex items-center justify-between">
                                        <template x-if="product.stock > 0">
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">
                                                In Stock (<span x-text="product.stock"></span>)
                                            </span>
                                        </template>
                                        <template x-if="product.stock === 0">
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">Out of Stock</span>
                                        </template>

                                        <span class="text-xs text-gray-500">MOQ: <span x-text="product.moq"></span></span>
                                    </div>

                                    <!-- Variant Count -->
                                    <div x-show="product.type === 'variable' && product.variant_count > 0" class="mt-2">
                                        <span class="text-xs text-gray-600">
                                            <span x-text="product.variant_count"></span> variants available
                                        </span>
                                    </div>

                                    <!-- Bundle Badge -->
                                    <div x-show="product.type === 'bundle'" class="mt-2">
                                        <span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded">
                                            Bundle Deal
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- List View -->
                    <div x-show="!loading && viewMode === 'list'" class="space-y-4">
                        <template x-for="product in products" :key="product.id">
                            <div @click="viewProduct(product)"
                                 class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer p-4 flex gap-4">
                                <!-- Product Image -->
                                <div class="w-32 h-32 flex-shrink-0 bg-gray-100 rounded flex items-center justify-center">
                                    <template x-if="product.image">
                                        <img :src="product.image" :alt="product.name" class="w-full h-full object-cover rounded">
                                    </template>
                                    <template x-if="!product.image">
                                        <span class="text-4xl">📦</span>
                                    </template>
                                </div>

                                <!-- Product Info -->
                                <div class="flex-1">
                                    <div class="flex items-start justify-between mb-2">
                                        <div>
                                            <h3 class="font-semibold text-lg text-gray-900" x-text="product.name"></h3>
                                            <p class="text-sm text-gray-600" x-text="product.sku"></p>
                                        </div>
                                        <span x-show="product.type && product.type !== 'simple'"
                                              class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs"
                                              x-text="product.type"></span>
                                    </div>

                                    <p class="text-sm text-gray-600 mb-3 line-clamp-2" x-text="product.description"></p>

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <template x-if="product.compare_price && product.compare_price > product.price">
                                                <div>
                                                    <span class="text-xl font-bold text-gray-900">$<span x-text="product.price"></span></span>
                                                    <span class="text-sm text-gray-500 line-through ml-2">$<span x-text="product.compare_price"></span></span>
                                                    <span class="text-xs text-green-600 ml-2">
                                                        Save <span x-text="Math.round(((product.compare_price - product.price) / product.compare_price) * 100)"></span>%
                                                    </span>
                                                </div>
                                            </template>
                                            <template x-if="!product.compare_price || product.compare_price <= product.price">
                                                <span class="text-xl font-bold text-gray-900">$<span x-text="product.price"></span></span>
                                            </template>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <template x-if="product.stock > 0">
                                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded text-sm">
                                                    In Stock (<span x-text="product.stock"></span>)
                                                </span>
                                            </template>
                                            <template x-if="product.stock === 0">
                                                <span class="px-3 py-1 bg-red-100 text-red-800 rounded text-sm">Out of Stock</span>
                                            </template>
                                            <span class="text-sm text-gray-500">MOQ: <span x-text="product.moq"></span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty State -->
                    <div x-show="!loading && products.length === 0" class="text-center py-12">
                        <div class="text-6xl mb-4">🔍</div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No products found</h3>
                        <p class="text-gray-600 mb-4">Try adjusting your filters or search term</p>
                        <button @click="clearFilters()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Clear Filters
                        </button>
                    </div>

                    <!-- Pagination -->
                    <div x-show="!loading && products.length > 0" class="mt-8 flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span> of <span x-text="pagination.total"></span> products
                        </div>
                        <div class="flex gap-2">
                            <button @click="loadPage(pagination.current_page - 1)"
                                    :disabled="pagination.current_page === 1"
                                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Previous
                            </button>
                            <template x-for="page in paginationPages" :key="page">
                                <button @click="loadPage(page)"
                                        :class="page === pagination.current_page ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-50'"
                                        class="px-4 py-2 border border-gray-300 rounded-lg"
                                        x-text="page"></button>
                            </template>
                            <button @click="loadPage(pagination.current_page + 1)"
                                    :disabled="pagination.current_page === pagination.last_page"
                                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const api = {
            client: {
                async get(url, params) {
                    const queryString = params ? '?' + new URLSearchParams(params).toString() : '';
                    await new Promise(resolve => setTimeout(resolve, 300));
                    return { data: getMockData(url, params) };
                }
            }
        };

        function getMockData(url, params) {
            if (url.includes('/categories')) {
                return {
                    data: [
                        { id: 1, name: 'Electronics', breadcrumb: 'Electronics' },
                        { id: 2, name: 'Laptops', breadcrumb: 'Electronics > Computers > Laptops' },
                        { id: 3, name: 'Fashion', breadcrumb: 'Fashion & Apparel' },
                        { id: 4, name: 'T-Shirts', breadcrumb: 'Fashion & Apparel > T-Shirts' },
                    ]
                };
            }
            if (url.includes('/attributes')) {
                return {
                    data: [
                        { id: 1, name: 'Size', slug: 'size', type: 'select', options: ['S', 'M', 'L', 'XL'], is_filterable: true },
                        { id: 2, name: 'Color', slug: 'color', type: 'select', options: ['Red', 'Blue', 'Green'], is_filterable: true },
                        { id: 3, name: 'Material', slug: 'material', type: 'select', options: ['Cotton', 'Polyester'], is_filterable: true },
                    ]
                };
            }
            if (url.includes('/products')) {
                return {
                    data: Array(12).fill(null).map((_, i) => ({
                        id: i + 1,
                        name: `Product ${i + 1}`,
                        sku: `SKU-${1000 + i}`,
                        description: 'High quality product with excellent features',
                        price: 50 + (i * 10),
                        compare_price: i % 3 === 0 ? 70 + (i * 10) : null,
                        stock: i % 4 === 0 ? 0 : 100 + i,
                        moq: 1,
                        type: ['simple', 'variable', 'bundle', 'configurable'][i % 4],
                        variant_count: i % 4 === 1 ? 6 : 0,
                        image: null,
                    })),
                    meta: {
                        current_page: params?.page || 1,
                        last_page: 3,
                        from: 1,
                        to: 12,
                        total: 36,
                        per_page: 12,
                    }
                };
            }
            return { data: [] };
        }

        function productCatalogData() {
            return {
                products: [],
                categories: [],
                categoryAttributes: [],
                loading: false,
                showFilters: true,
                viewMode: 'grid',
                sortBy: 'created_desc',

                filters: {
                    search: '',
                    category_id: '',
                    min_price: '',
                    max_price: '',
                    in_stock: false,
                    attributes: {},
                },

                pagination: {
                    current_page: 1,
                    last_page: 1,
                    from: 0,
                    to: 0,
                    total: 0,
                    per_page: 12,
                },

                async init() {
                    await this.loadCategories();
                    await this.loadProducts();
                },

                async loadCategories() {
                    const response = await api.client.get('/api/admin/categories');
                    this.categories = response.data.data || [];
                },

                async loadCategoryAttributes() {
                    if (!this.filters.category_id) {
                        this.categoryAttributes = [];
                        return;
                    }

                    const response = await api.client.get(`/api/admin/categories/${this.filters.category_id}/attributes`);
                    this.categoryAttributes = response.data.data || [];
                },

                get filterableAttributes() {
                    return this.categoryAttributes.filter(attr => attr.is_filterable);
                },

                async loadProducts(page = 1) {
                    this.loading = true;

                    const params = {
                        page,
                        per_page: this.pagination.per_page,
                        sort: this.sortBy,
                        ...this.filters,
                    };

                    const response = await api.client.get('/api/vendor/products', params);
                    this.products = response.data.data || [];
                    this.pagination = response.data.meta || this.pagination;

                    this.loading = false;
                },

                async loadPage(page) {
                    if (page >= 1 && page <= this.pagination.last_page) {
                        await this.loadProducts(page);
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },

                get paginationPages() {
                    const pages = [];
                    const total = this.pagination.last_page;
                    const current = this.pagination.current_page;

                    for (let i = Math.max(1, current - 2); i <= Math.min(total, current + 2); i++) {
                        pages.push(i);
                    }
                    return pages;
                },

                get activeFiltersCount() {
                    let count = 0;
                    if (this.filters.search) count++;
                    if (this.filters.category_id) count++;
                    if (this.filters.min_price) count++;
                    if (this.filters.max_price) count++;
                    if (this.filters.in_stock) count++;
                    count += Object.keys(this.filters.attributes).filter(key => this.filters.attributes[key]).length;
                    return count;
                },

                get activeFiltersList() {
                    const list = [];
                    if (this.filters.search) {
                        list.push({ key: 'search', label: 'Search', value: this.filters.search });
                    }
                    if (this.filters.category_id) {
                        const cat = this.categories.find(c => c.id == this.filters.category_id);
                        list.push({ key: 'category_id', label: 'Category', value: cat?.name || '' });
                    }
                    if (this.filters.min_price) {
                        list.push({ key: 'min_price', label: 'Min Price', value: '$' + this.filters.min_price });
                    }
                    if (this.filters.max_price) {
                        list.push({ key: 'max_price', label: 'Max Price', value: '$' + this.filters.max_price });
                    }
                    if (this.filters.in_stock) {
                        list.push({ key: 'in_stock', label: 'Stock', value: 'In Stock Only' });
                    }
                    return list;
                },

                removeFilter(key) {
                    if (key === 'search') this.filters.search = '';
                    else if (key === 'category_id') this.filters.category_id = '';
                    else if (key === 'min_price') this.filters.min_price = '';
                    else if (key === 'max_price') this.filters.max_price = '';
                    else if (key === 'in_stock') this.filters.in_stock = false;
                    this.loadProducts();
                },

                clearFilters() {
                    this.filters = {
                        search: '',
                        category_id: '',
                        min_price: '',
                        max_price: '',
                        in_stock: false,
                        attributes: {},
                    };
                    this.loadProducts();
                },

                viewProduct(product) {
                    window.location.href = `/vendor/products/${product.id}`;
                }
            };
        }
    </script>
</body>
</html>

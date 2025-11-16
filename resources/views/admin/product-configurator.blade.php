<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Configurator - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50">
    <div x-data="productConfiguratorData()" x-init="init()" class="min-h-screen p-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Product Configurator</h1>
                    <p class="text-gray-600 mt-1">Create and configure products of any type</p>
                </div>
                <div class="flex gap-3">
                    <button @click="resetForm()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        Reset
                    </button>
                    <button @click="saveProduct()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Save Product
                    </button>
                </div>
            </div>
        </div>

        <!-- Product Type Selection -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">1. Product Type</h2>
            <div class="grid grid-cols-4 gap-4">
                <template x-for="type in productTypes" :key="type.value">
                    <div @click="selectProductType(type.value)"
                         :class="productForm.type === type.value ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-300'"
                         class="border-2 rounded-lg p-4 cursor-pointer transition-all">
                        <div class="text-4xl mb-2" x-text="type.icon"></div>
                        <h3 class="font-semibold text-gray-900" x-text="type.label"></h3>
                        <p class="text-sm text-gray-600 mt-1" x-text="type.description"></p>
                    </div>
                </template>
            </div>
        </div>

        <!-- Basic Information -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">2. Basic Information</h2>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                    <input type="text" x-model="productForm.name"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Enter product name">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">SKU *</label>
                    <input type="text" x-model="productForm.sku"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Enter SKU">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea x-model="productForm.description" rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Enter product description"></textarea>
                </div>
            </div>
        </div>

        <!-- Category Selection -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">3. Category & Attributes</h2>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                <select x-model="productForm.category_id" @change="loadCategoryAttributes()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Select a category</option>
                    <template x-for="category in categories" :key="category.id">
                        <option :value="category.id" x-text="category.breadcrumb || category.name"></option>
                    </template>
                </select>
            </div>

            <!-- Dynamic Attributes -->
            <div x-show="categoryAttributes.length > 0" x-cloak>
                <h3 class="text-lg font-medium mb-4">Category Attributes</h3>
                <div class="grid grid-cols-2 gap-6">
                    <template x-for="attr in categoryAttributes" :key="attr.id">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <span x-text="attr.name"></span>
                                <span x-show="attr.pivot?.is_required" class="text-red-500">*</span>
                            </label>

                            <!-- Text Input -->
                            <template x-if="attr.type === 'text'">
                                <input type="text" x-model="productForm.attributes[attr.slug]"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </template>

                            <!-- Number Input -->
                            <template x-if="attr.type === 'number'">
                                <input type="number" x-model="productForm.attributes[attr.slug]"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </template>

                            <!-- Select -->
                            <template x-if="attr.type === 'select'">
                                <select x-model="productForm.attributes[attr.slug]"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Select...</option>
                                    <template x-for="option in attr.options" :key="option">
                                        <option :value="option" x-text="option"></option>
                                    </template>
                                </select>
                            </template>

                            <!-- Multiselect -->
                            <template x-if="attr.type === 'multiselect'">
                                <select multiple x-model="productForm.attributes[attr.slug]"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <template x-for="option in attr.options" :key="option">
                                        <option :value="option" x-text="option"></option>
                                    </template>
                                </select>
                            </template>

                            <!-- Color -->
                            <template x-if="attr.type === 'color'">
                                <div class="flex gap-2">
                                    <input type="color" x-model="productForm.attributes[attr.slug]"
                                           class="h-10 w-20 border border-gray-300 rounded-lg">
                                    <input type="text" x-model="productForm.attributes[attr.slug]"
                                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="#000000">
                                </div>
                            </template>

                            <!-- Boolean -->
                            <template x-if="attr.type === 'boolean'">
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="productForm.attributes[attr.slug]"
                                           class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-600">Yes</span>
                                </label>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Pricing (Simple/Variable) -->
        <div x-show="['simple', 'variable'].includes(productForm.type)" x-cloak class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">4. Pricing & Stock</h2>
            <div class="grid grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Base Price *</label>
                    <input type="number" step="0.01" x-model="productForm.price"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="0.00">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Compare Price</label>
                    <input type="number" step="0.01" x-model="productForm.compare_price"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="0.00">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity *</label>
                    <input type="number" x-model="productForm.stock"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">MOQ (Minimum Order) *</label>
                    <input type="number" x-model="productForm.moq"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="1">
                </div>
            </div>
        </div>

        <!-- Variable Product: Variant Builder -->
        <div x-show="productForm.type === 'variable'" x-cloak class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">5. Variant Configuration</h2>
                <button @click="generateVariants()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Generate Variants
                </button>
            </div>

            <!-- Select Variant Attributes -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Variant Attributes</label>
                <div class="flex flex-wrap gap-3">
                    <template x-for="attr in variantAttributes" :key="attr.id">
                        <label class="flex items-center px-4 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" :value="attr.id" x-model="selectedVariantAttributes"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm font-medium" x-text="attr.name"></span>
                        </label>
                    </template>
                </div>
            </div>

            <!-- Generated Variants Table -->
            <div x-show="productForm.variants.length > 0" x-cloak>
                <h3 class="text-lg font-medium mb-3">Generated Variants (<span x-text="productForm.variants.length"></span>)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variant</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(variant, index) in productForm.variants" :key="index">
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900" x-text="variant.name"></div>
                                        <div class="text-sm text-gray-500" x-text="JSON.stringify(variant.attributes)"></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" x-model="variant.sku"
                                               class="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                               placeholder="SKU">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" step="0.01" x-model="variant.price"
                                               class="w-24 px-2 py-1 border border-gray-300 rounded text-sm"
                                               placeholder="0.00">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" x-model="variant.stock"
                                               class="w-20 px-2 py-1 border border-gray-300 rounded text-sm"
                                               placeholder="0">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="checkbox" x-model="variant.is_active"
                                               class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                    </td>
                                    <td class="px-4 py-3">
                                        <button @click="removeVariant(index)" class="text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Bundle Product: Bundle Builder -->
        <div x-show="productForm.type === 'bundle'" x-cloak class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">5. Bundle Configuration</h2>
                <button @click="showAddBundleItem = true" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Add Product
                </button>
            </div>

            <!-- Bundle Items -->
            <div x-show="productForm.bundle_items.length > 0" x-cloak>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount %</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Final Price</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(item, index) in productForm.bundle_items" :key="index">
                            <tr>
                                <td class="px-4 py-3 font-medium" x-text="item.product_name"></td>
                                <td class="px-4 py-3">
                                    <input type="number" x-model="item.quantity" min="1"
                                           @change="calculateBundlePrice()"
                                           class="w-20 px-2 py-1 border border-gray-300 rounded text-sm">
                                </td>
                                <td class="px-4 py-3" x-text="'$' + item.unit_price"></td>
                                <td class="px-4 py-3">
                                    <input type="number" step="0.01" x-model="item.discount_percentage" min="0" max="100"
                                           @change="calculateBundlePrice()"
                                           class="w-20 px-2 py-1 border border-gray-300 rounded text-sm">
                                </td>
                                <td class="px-4 py-3 font-semibold" x-text="'$' + calculateItemPrice(item)"></td>
                                <td class="px-4 py-3">
                                    <button @click="removeBundleItem(index)" class="text-red-600 hover:text-red-900">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right font-semibold">Total Bundle Price:</td>
                            <td class="px-4 py-3 font-bold text-lg text-green-600" x-text="'$' + bundleTotalPrice.toFixed(2)"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Add Bundle Item Modal -->
            <div x-show="showAddBundleItem" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
                    <h3 class="text-xl font-semibold mb-4">Add Product to Bundle</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Product</label>
                            <select x-model="bundleItemForm.product_id" @change="selectBundleProduct()"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="">Choose a product...</option>
                                <template x-for="product in availableProducts" :key="product.id">
                                    <option :value="product.id" x-text="product.name + ' ($' + product.price + ')'"></option>
                                </template>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                                <input type="number" x-model="bundleItemForm.quantity" min="1"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Discount %</label>
                                <input type="number" step="0.01" x-model="bundleItemForm.discount_percentage" min="0" max="100"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button @click="showAddBundleItem = false" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button @click="addBundleItem()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Add Product
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configurable Product: Options Builder -->
        <div x-show="productForm.type === 'configurable'" x-cloak class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">5. Custom Options</h2>
                <button @click="addCustomOption()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Add Option
                </button>
            </div>

            <div class="space-y-4">
                <template x-for="(option, index) in productForm.custom_options" :key="index">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-medium">Option <span x-text="index + 1"></span></h4>
                            <button @click="removeCustomOption(index)" class="text-red-600 hover:text-red-900">
                                Remove
                            </button>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Option Name</label>
                                <input type="text" x-model="option.name"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                       placeholder="e.g., Engraving">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                                <select x-model="option.type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="text">Text Input</option>
                                    <option value="select">Dropdown</option>
                                    <option value="checkbox">Checkbox</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Price Modifier</label>
                                <input type="number" step="0.01" x-model="option.price_modifier"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                       placeholder="0.00">
                            </div>
                        </div>
                        <!-- Options for select type -->
                        <div x-show="option.type === 'select'" class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Values (one per line)</label>
                            <textarea x-model="option.values" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                      placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                        </div>
                        <div class="mt-3">
                            <label class="flex items-center">
                                <input type="checkbox" x-model="option.required"
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                <span class="ml-2 text-sm">Required</span>
                            </label>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Success Message -->
        <div x-show="showSuccess" x-cloak
             class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
            Product saved successfully!
        </div>
    </div>

    <script>
        const api = {
            client: {
                async get(url) {
                    await new Promise(resolve => setTimeout(resolve, 300));
                    return { data: getMockData(url) };
                },
                async post(url, data) {
                    console.log('POST', url, data);
                    await new Promise(resolve => setTimeout(resolve, 300));
                    return { data: { id: Math.random() } };
                },
                async put(url, data) {
                    console.log('PUT', url, data);
                    await new Promise(resolve => setTimeout(resolve, 300));
                    return { data: { success: true } };
                }
            }
        };

        function getMockData(url) {
            if (url.includes('/categories')) {
                return {
                    data: [
                        { id: 1, name: 'Electronics', breadcrumb: 'Electronics', parent_id: null },
                        { id: 2, name: 'Laptops', breadcrumb: 'Electronics > Laptops', parent_id: 1 },
                        { id: 3, name: 'Fashion', breadcrumb: 'Fashion', parent_id: null },
                        { id: 4, name: 'T-Shirts', breadcrumb: 'Fashion > Men\'s Clothing > T-Shirts', parent_id: 3 },
                    ]
                };
            }
            if (url.includes('/attributes')) {
                return {
                    data: [
                        { id: 1, name: 'Processor', slug: 'processor', type: 'select', options: ['Intel i5', 'Intel i7', 'AMD Ryzen'], is_variant: false },
                        { id: 2, name: 'RAM', slug: 'ram', type: 'select', options: ['8GB', '16GB', '32GB'], is_variant: false },
                        { id: 3, name: 'Size', slug: 'size', type: 'select', options: ['S', 'M', 'L', 'XL'], is_variant: true },
                        { id: 4, name: 'Color', slug: 'color', type: 'color', is_variant: true },
                    ]
                };
            }
            if (url.includes('/products')) {
                return {
                    data: [
                        { id: 1, name: 'Product A', price: 100 },
                        { id: 2, name: 'Product B', price: 200 },
                        { id: 3, name: 'Product C', price: 150 },
                    ]
                };
            }
            return { data: [] };
        }

        function productConfiguratorData() {
            return {
                productTypes: [
                    { value: 'simple', label: 'Simple Product', icon: '📦', description: 'Standard product with single price' },
                    { value: 'variable', label: 'Variable Product', icon: '🎨', description: 'Product with variants (color, size, etc.)' },
                    { value: 'bundle', label: 'Bundle Product', icon: '📦📦', description: 'Package of multiple products' },
                    { value: 'configurable', label: 'Configurable Product', icon: '⚙️', description: 'Product with custom options' },
                ],

                productForm: {
                    type: 'simple',
                    name: '',
                    sku: '',
                    description: '',
                    category_id: '',
                    attributes: {},
                    price: 0,
                    compare_price: null,
                    stock: 0,
                    moq: 1,
                    variants: [],
                    bundle_items: [],
                    custom_options: [],
                },

                categories: [],
                categoryAttributes: [],
                variantAttributes: [],
                selectedVariantAttributes: [],
                availableProducts: [],

                bundleItemForm: {
                    product_id: '',
                    quantity: 1,
                    discount_percentage: 0,
                },

                showAddBundleItem: false,
                showSuccess: false,

                async init() {
                    await this.loadCategories();
                    await this.loadProducts();
                },

                async loadCategories() {
                    const response = await api.client.get('/admin/categories');
                    this.categories = response.data.data || [];
                },

                async loadProducts() {
                    const response = await api.client.get('/admin/products');
                    this.availableProducts = response.data.data || [];
                },

                async loadCategoryAttributes() {
                    if (!this.productForm.category_id) {
                        this.categoryAttributes = [];
                        return;
                    }

                    const response = await api.client.get(`/admin/categories/${this.productForm.category_id}/attributes`);
                    this.categoryAttributes = response.data.data || [];

                    // Filter variant attributes
                    this.variantAttributes = this.categoryAttributes.filter(attr => attr.is_variant);
                },

                selectProductType(type) {
                    this.productForm.type = type;
                    // Reset type-specific data
                    this.productForm.variants = [];
                    this.productForm.bundle_items = [];
                    this.productForm.custom_options = [];
                },

                // Variant Generation
                generateVariants() {
                    if (this.selectedVariantAttributes.length === 0) {
                        alert('Please select at least one variant attribute');
                        return;
                    }

                    // Get selected attributes with their options
                    const selectedAttrs = this.variantAttributes.filter(attr =>
                        this.selectedVariantAttributes.includes(attr.id)
                    );

                    // Generate all combinations
                    const combinations = this.cartesianProduct(
                        selectedAttrs.map(attr => ({
                            slug: attr.slug,
                            name: attr.name,
                            values: attr.options || []
                        }))
                    );

                    // Create variants
                    this.productForm.variants = combinations.map((combo, index) => {
                        const variantName = Object.values(combo).join(' / ');
                        const variantSku = this.productForm.sku + '-' + Object.values(combo).join('-').toLowerCase();

                        return {
                            name: variantName,
                            sku: variantSku,
                            attributes: combo,
                            price: this.productForm.price,
                            stock: 0,
                            moq: this.productForm.moq,
                            is_active: true
                        };
                    });
                },

                cartesianProduct(arrays) {
                    if (arrays.length === 0) return [{}];

                    const [first, ...rest] = arrays;
                    const restProduct = this.cartesianProduct(rest);

                    return first.values.flatMap(value =>
                        restProduct.map(combo => ({
                            [first.slug]: value,
                            ...combo
                        }))
                    );
                },

                removeVariant(index) {
                    this.productForm.variants.splice(index, 1);
                },

                // Bundle Management
                selectBundleProduct() {
                    const product = this.availableProducts.find(p => p.id == this.bundleItemForm.product_id);
                    if (product) {
                        this.bundleItemForm.unit_price = product.price;
                        this.bundleItemForm.product_name = product.name;
                    }
                },

                addBundleItem() {
                    if (!this.bundleItemForm.product_id) {
                        alert('Please select a product');
                        return;
                    }

                    this.productForm.bundle_items.push({
                        ...this.bundleItemForm
                    });

                    // Reset form
                    this.bundleItemForm = {
                        product_id: '',
                        quantity: 1,
                        discount_percentage: 0,
                    };
                    this.showAddBundleItem = false;
                    this.calculateBundlePrice();
                },

                removeBundleItem(index) {
                    this.productForm.bundle_items.splice(index, 1);
                    this.calculateBundlePrice();
                },

                calculateItemPrice(item) {
                    const basePrice = item.unit_price * item.quantity;
                    const discount = basePrice * (item.discount_percentage / 100);
                    return (basePrice - discount).toFixed(2);
                },

                calculateBundlePrice() {
                    // Recalculate in next tick to ensure reactivity
                    this.$nextTick(() => {
                        this.bundleTotalPrice;
                    });
                },

                get bundleTotalPrice() {
                    return this.productForm.bundle_items.reduce((total, item) => {
                        return total + parseFloat(this.calculateItemPrice(item));
                    }, 0);
                },

                // Custom Options
                addCustomOption() {
                    this.productForm.custom_options.push({
                        name: '',
                        type: 'text',
                        values: '',
                        price_modifier: 0,
                        required: false
                    });
                },

                removeCustomOption(index) {
                    this.productForm.custom_options.splice(index, 1);
                },

                // Save Product
                async saveProduct() {
                    // Validation
                    if (!this.productForm.name || !this.productForm.sku) {
                        alert('Please fill in all required fields');
                        return;
                    }

                    // Save based on type
                    const response = await api.client.post('/admin/products', this.productForm);

                    this.showSuccess = true;
                    setTimeout(() => {
                        this.showSuccess = false;
                    }, 3000);

                    console.log('Product saved:', response.data);
                },

                resetForm() {
                    this.productForm = {
                        type: 'simple',
                        name: '',
                        sku: '',
                        description: '',
                        category_id: '',
                        attributes: {},
                        price: 0,
                        compare_price: null,
                        stock: 0,
                        moq: 1,
                        variants: [],
                        bundle_items: [],
                        custom_options: [],
                    };
                    this.selectedVariantAttributes = [];
                }
            };
        }
    </script>
</body>
</html>

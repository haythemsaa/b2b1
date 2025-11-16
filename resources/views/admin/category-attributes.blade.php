<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Attributes - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50">
    <div x-data="categoryAttributesData()" x-init="init()" class="min-h-screen p-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Category Attributes Assignment</h1>
            <p class="text-gray-600 mt-1">Configure which attributes appear for each category</p>
        </div>

        <!-- Category Selector -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <label class="block text-lg font-medium text-gray-900 mb-3">Select Category</label>
            <select x-model="selectedCategoryId" @change="loadCategoryAttributes()"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg">
                <option value="">Choose a category...</option>
                <template x-for="category in categories" :key="category.id">
                    <option :value="category.id" x-text="category.breadcrumb || category.name"></option>
                </template>
            </select>
        </div>

        <!-- Attribute Assignment Interface -->
        <div x-show="selectedCategoryId" x-cloak>
            <!-- Stats -->
            <div class="grid grid-cols-4 gap-6 mb-6">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-sm p-6 text-white">
                    <div class="text-3xl font-bold" x-text="assignedAttributes.length"></div>
                    <div class="text-blue-100 mt-1">Assigned Attributes</div>
                </div>
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-sm p-6 text-white">
                    <div class="text-3xl font-bold" x-text="requiredCount"></div>
                    <div class="text-green-100 mt-1">Required</div>
                </div>
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-sm p-6 text-white">
                    <div class="text-3xl font-bold" x-text="variantCount"></div>
                    <div class="text-purple-100 mt-1">Variant Attributes</div>
                </div>
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-sm p-6 text-white">
                    <div class="text-3xl font-bold" x-text="availableAttributes.length"></div>
                    <div class="text-orange-100 mt-1">Available to Add</div>
                </div>
            </div>

            <!-- Add Attributes Section -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Available Attributes</h2>
                    <div class="flex gap-3">
                        <input type="text" x-model="searchQuery" placeholder="Search attributes..."
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <select x-model="filterType"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Types</option>
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="select">Select</option>
                            <option value="multiselect">Multiselect</option>
                            <option value="color">Color</option>
                            <option value="boolean">Boolean</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <template x-for="attr in filteredAvailableAttributes" :key="attr.id">
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900" x-text="attr.name"></h3>
                                    <p class="text-sm text-gray-500" x-text="attr.slug"></p>
                                </div>
                                <span class="px-2 py-1 rounded text-xs font-medium"
                                      :class="{
                                          'bg-blue-100 text-blue-800': attr.type === 'text',
                                          'bg-green-100 text-green-800': attr.type === 'number',
                                          'bg-purple-100 text-purple-800': attr.type === 'select',
                                          'bg-pink-100 text-pink-800': attr.type === 'multiselect',
                                          'bg-yellow-100 text-yellow-800': attr.type === 'color',
                                          'bg-gray-100 text-gray-800': attr.type === 'boolean',
                                      }"
                                      x-text="attr.type"></span>
                            </div>
                            <div class="flex flex-wrap gap-2 mb-3">
                                <span x-show="attr.is_filterable" class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs">
                                    Filterable
                                </span>
                                <span x-show="attr.is_variant" class="px-2 py-1 bg-purple-50 text-purple-700 rounded text-xs">
                                    Variant
                                </span>
                            </div>
                            <button @click="addAttribute(attr)"
                                    class="w-full px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Add to Category
                            </button>
                        </div>
                    </template>
                </div>

                <div x-show="filteredAvailableAttributes.length === 0" class="text-center py-8 text-gray-500">
                    No available attributes found
                </div>
            </div>

            <!-- Assigned Attributes Section -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold mb-4">Assigned Attributes</h2>

                <div x-show="assignedAttributes.length === 0" class="text-center py-8 text-gray-500">
                    No attributes assigned to this category yet
                </div>

                <div x-show="assignedAttributes.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attribute</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Properties</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Required</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Display Order</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(attr, index) in sortedAssignedAttributes" :key="attr.id">
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-1">
                                            <button @click="moveUp(index)" :disabled="index === 0"
                                                    class="text-gray-600 hover:text-blue-600 disabled:opacity-30">
                                                ↑
                                            </button>
                                            <button @click="moveDown(index)" :disabled="index === assignedAttributes.length - 1"
                                                    class="text-gray-600 hover:text-blue-600 disabled:opacity-30">
                                                ↓
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900" x-text="attr.name"></div>
                                        <div class="text-sm text-gray-500" x-text="attr.slug"></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded text-xs font-medium"
                                              :class="{
                                                  'bg-blue-100 text-blue-800': attr.type === 'text',
                                                  'bg-green-100 text-green-800': attr.type === 'number',
                                                  'bg-purple-100 text-purple-800': attr.type === 'select',
                                                  'bg-pink-100 text-pink-800': attr.type === 'multiselect',
                                                  'bg-yellow-100 text-yellow-800': attr.type === 'color',
                                                  'bg-gray-100 text-gray-800': attr.type === 'boolean',
                                              }"
                                              x-text="attr.type"></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            <span x-show="attr.is_filterable" class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs">
                                                Filterable
                                            </span>
                                            <span x-show="attr.is_variant" class="px-2 py-1 bg-purple-50 text-purple-700 rounded text-xs">
                                                Variant
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <label class="flex items-center">
                                            <input type="checkbox" x-model="attr.pivot.is_required"
                                                   @change="updateAttribute(attr)"
                                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                            <span class="ml-2 text-sm" x-text="attr.pivot.is_required ? 'Yes' : 'No'"></span>
                                        </label>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" x-model="attr.pivot.order" min="0"
                                               @change="updateAttribute(attr)"
                                               class="w-20 px-2 py-1 border border-gray-300 rounded text-sm">
                                    </td>
                                    <td class="px-4 py-3">
                                        <button @click="removeAttribute(attr)" class="text-red-600 hover:text-red-900">
                                            Remove
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div x-show="assignedAttributes.length > 0" class="bg-white rounded-lg shadow-sm p-6 mt-6">
                <h3 class="text-lg font-semibold mb-4">Bulk Actions</h3>
                <div class="flex gap-3">
                    <button @click="saveAllChanges()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        Save All Changes
                    </button>
                    <button @click="makeAllRequired()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Make All Required
                    </button>
                    <button @click="makeAllOptional()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        Make All Optional
                    </button>
                    <button @click="removeAllAttributes()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Remove All
                    </button>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <div x-show="showSuccess" x-cloak
             class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
            <span x-text="successMessage"></span>
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
                    return { data: { success: true } };
                },
                async put(url, data) {
                    console.log('PUT', url, data);
                    await new Promise(resolve => setTimeout(resolve, 300));
                    return { data: { success: true } };
                },
                async delete(url) {
                    console.log('DELETE', url);
                    await new Promise(resolve => setTimeout(resolve, 300));
                    return { data: { success: true } };
                }
            }
        };

        function getMockData(url) {
            if (url.includes('/categories') && !url.includes('/attributes')) {
                return {
                    data: [
                        { id: 1, name: 'Electronics', breadcrumb: 'Electronics', parent_id: null },
                        { id: 2, name: 'Laptops', breadcrumb: 'Electronics > Computers > Laptops', parent_id: 1 },
                        { id: 3, name: 'Desktops', breadcrumb: 'Electronics > Computers > Desktops', parent_id: 1 },
                        { id: 4, name: 'Fashion', breadcrumb: 'Fashion & Apparel', parent_id: null },
                        { id: 5, name: 'T-Shirts', breadcrumb: 'Fashion & Apparel > Men\'s Clothing > T-Shirts', parent_id: 4 },
                        { id: 6, name: 'Shirts', breadcrumb: 'Fashion & Apparel > Men\'s Clothing > Shirts', parent_id: 4 },
                    ]
                };
            }
            if (url.includes('/attributes')) {
                return {
                    data: [
                        { id: 1, name: 'Processor', slug: 'processor', type: 'select', options: ['Intel i5', 'Intel i7', 'AMD Ryzen'], is_filterable: true, is_variant: false },
                        { id: 2, name: 'RAM', slug: 'ram', type: 'select', options: ['8GB', '16GB', '32GB'], is_filterable: true, is_variant: false },
                        { id: 3, name: 'Storage', slug: 'storage', type: 'select', options: ['256GB', '512GB', '1TB'], is_filterable: true, is_variant: false },
                        { id: 4, name: 'Screen Size', slug: 'screen-size', type: 'select', options: ['13"', '15"', '17"'], is_filterable: true, is_variant: false },
                        { id: 5, name: 'Size', slug: 'size', type: 'select', options: ['S', 'M', 'L', 'XL'], is_filterable: true, is_variant: true },
                        { id: 6, name: 'Color', slug: 'color', type: 'color', is_filterable: true, is_variant: true },
                        { id: 7, name: 'Material', slug: 'material', type: 'select', options: ['Cotton', 'Polyester', 'Wool'], is_filterable: true, is_variant: false },
                        { id: 8, name: 'Season', slug: 'season', type: 'select', options: ['Spring', 'Summer', 'Fall', 'Winter'], is_filterable: true, is_variant: false },
                    ]
                };
            }
            if (url.includes('/category-attributes')) {
                // Mock assigned attributes for category 2 (Laptops)
                return {
                    data: [
                        { id: 1, name: 'Processor', slug: 'processor', type: 'select', is_filterable: true, is_variant: false, pivot: { is_required: true, order: 1 } },
                        { id: 2, name: 'RAM', slug: 'ram', type: 'select', is_filterable: true, is_variant: false, pivot: { is_required: true, order: 2 } },
                        { id: 3, name: 'Storage', slug: 'storage', type: 'select', is_filterable: true, is_variant: false, pivot: { is_required: true, order: 3 } },
                    ]
                };
            }
            return { data: [] };
        }

        function categoryAttributesData() {
            return {
                selectedCategoryId: '',
                categories: [],
                allAttributes: [],
                assignedAttributes: [],
                searchQuery: '',
                filterType: '',
                showSuccess: false,
                successMessage: '',

                async init() {
                    await this.loadCategories();
                    await this.loadAllAttributes();
                },

                async loadCategories() {
                    const response = await api.client.get('/admin/categories');
                    this.categories = response.data.data || [];
                },

                async loadAllAttributes() {
                    const response = await api.client.get('/admin/attributes');
                    this.allAttributes = response.data.data || [];
                },

                async loadCategoryAttributes() {
                    if (!this.selectedCategoryId) {
                        this.assignedAttributes = [];
                        return;
                    }

                    const response = await api.client.get(`/admin/categories/${this.selectedCategoryId}/attributes`);
                    this.assignedAttributes = response.data.data || [];
                },

                get availableAttributes() {
                    const assignedIds = this.assignedAttributes.map(attr => attr.id);
                    return this.allAttributes.filter(attr => !assignedIds.includes(attr.id));
                },

                get filteredAvailableAttributes() {
                    let filtered = this.availableAttributes;

                    if (this.searchQuery) {
                        const query = this.searchQuery.toLowerCase();
                        filtered = filtered.filter(attr =>
                            attr.name.toLowerCase().includes(query) ||
                            attr.slug.toLowerCase().includes(query)
                        );
                    }

                    if (this.filterType) {
                        filtered = filtered.filter(attr => attr.type === this.filterType);
                    }

                    return filtered;
                },

                get sortedAssignedAttributes() {
                    return [...this.assignedAttributes].sort((a, b) =>
                        (a.pivot?.order || 0) - (b.pivot?.order || 0)
                    );
                },

                get requiredCount() {
                    return this.assignedAttributes.filter(attr => attr.pivot?.is_required).length;
                },

                get variantCount() {
                    return this.assignedAttributes.filter(attr => attr.is_variant).length;
                },

                async addAttribute(attribute) {
                    const data = {
                        attribute_id: attribute.id,
                        is_required: false,
                        order: this.assignedAttributes.length
                    };

                    await api.client.post(`/admin/categories/${this.selectedCategoryId}/attributes`, data);

                    this.assignedAttributes.push({
                        ...attribute,
                        pivot: {
                            is_required: false,
                            order: this.assignedAttributes.length
                        }
                    });

                    this.showSuccessMessage('Attribute added successfully');
                },

                async removeAttribute(attribute) {
                    if (!confirm(`Remove "${attribute.name}" from this category?`)) return;

                    await api.client.delete(`/admin/categories/${this.selectedCategoryId}/attributes/${attribute.id}`);

                    const index = this.assignedAttributes.findIndex(a => a.id === attribute.id);
                    if (index !== -1) {
                        this.assignedAttributes.splice(index, 1);
                    }

                    this.showSuccessMessage('Attribute removed');
                },

                async updateAttribute(attribute) {
                    await api.client.put(
                        `/admin/categories/${this.selectedCategoryId}/attributes/${attribute.id}`,
                        {
                            is_required: attribute.pivot.is_required,
                            order: attribute.pivot.order
                        }
                    );
                },

                moveUp(index) {
                    if (index === 0) return;

                    const sorted = this.sortedAssignedAttributes;
                    const temp = sorted[index].pivot.order;
                    sorted[index].pivot.order = sorted[index - 1].pivot.order;
                    sorted[index - 1].pivot.order = temp;

                    this.updateAttribute(sorted[index]);
                    this.updateAttribute(sorted[index - 1]);
                },

                moveDown(index) {
                    const sorted = this.sortedAssignedAttributes;
                    if (index === sorted.length - 1) return;

                    const temp = sorted[index].pivot.order;
                    sorted[index].pivot.order = sorted[index + 1].pivot.order;
                    sorted[index + 1].pivot.order = temp;

                    this.updateAttribute(sorted[index]);
                    this.updateAttribute(sorted[index + 1]);
                },

                async saveAllChanges() {
                    for (const attr of this.assignedAttributes) {
                        await this.updateAttribute(attr);
                    }
                    this.showSuccessMessage('All changes saved');
                },

                makeAllRequired() {
                    this.assignedAttributes.forEach(attr => {
                        attr.pivot.is_required = true;
                        this.updateAttribute(attr);
                    });
                    this.showSuccessMessage('All attributes marked as required');
                },

                makeAllOptional() {
                    this.assignedAttributes.forEach(attr => {
                        attr.pivot.is_required = false;
                        this.updateAttribute(attr);
                    });
                    this.showSuccessMessage('All attributes marked as optional');
                },

                async removeAllAttributes() {
                    if (!confirm('Remove all attributes from this category?')) return;

                    for (const attr of [...this.assignedAttributes]) {
                        await this.removeAttribute(attr);
                    }
                    this.showSuccessMessage('All attributes removed');
                },

                showSuccessMessage(message) {
                    this.successMessage = message;
                    this.showSuccess = true;
                    setTimeout(() => {
                        this.showSuccess = false;
                    }, 3000);
                }
            };
        }
    </script>
</body>
</html>

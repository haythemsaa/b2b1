@extends('layouts.app')

@section('title', 'AI Recommendations')

@section('sidebar')
    @include('vendor.partials.sidebar')
@endsection

@section('content')
<div x-data="recommendationsData()" x-init="init()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">AI Recommendations</h1>
        <p class="text-gray-600 mt-1">Personalized product recommendations and order predictions powered by AI</p>
    </div>

    <!-- Action Buttons -->
    <div class="mb-6 flex items-center space-x-4">
        <button
            @click="generateRecommendations()"
            :disabled="generating"
            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium disabled:opacity-50"
        >
            <span x-show="!generating">Generate New Recommendations</span>
            <span x-show="generating">Generating...</span>
        </button>
        <button
            @click="generatePredictions()"
            :disabled="generating"
            class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium disabled:opacity-50"
        >
            <span x-show="!generating">Generate Predictions</span>
            <span x-show="generating">Generating...</span>
        </button>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button
                @click="activeTab = 'personalized'"
                :class="activeTab === 'personalized' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="py-4 px-1 border-b-2 font-medium text-sm"
            >
                Personalized Recommendations
            </button>
            <button
                @click="activeTab = 'trending'"
                :class="activeTab === 'trending' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="py-4 px-1 border-b-2 font-medium text-sm"
            >
                Trending Products
            </button>
            <button
                @click="activeTab = 'predictions'"
                :class="activeTab === 'predictions' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="py-4 px-1 border-b-2 font-medium text-sm"
            >
                Order Predictions
            </button>
        </nav>
    </div>

    <!-- Personalized Recommendations -->
    <div x-show="activeTab === 'personalized'">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="rec in personalizedRecommendations" :key="rec.id">
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <span class="text-xs font-semibold text-blue-600 uppercase" x-text="rec.product?.category"></span>
                            <h3 class="text-lg font-semibold text-gray-900 mt-1" x-text="rec.product?.name"></h3>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-600" x-text="(rec.confidence * 100).toFixed(0) + '%'"></span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-2">Recommendation Type:</p>
                        <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full" x-text="rec.recommendation_type.replace('_', ' ')"></span>
                    </div>

                    <div class="mb-4">
                        <p class="text-2xl font-bold text-gray-900" x-text="formatCurrency(rec.product?.price)"></p>
                        <p class="text-xs text-gray-600">MOQ: <span x-text="rec.product?.moq"></span></p>
                    </div>

                    <div class="flex space-x-2">
                        <a :href="`/vendor/products/${rec.product_id}`" class="flex-1 px-4 py-2 bg-blue-600 text-white text-center rounded-lg hover:bg-blue-700 text-sm font-medium">
                            View Product
                        </a>
                        <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>

            <div x-show="personalizedRecommendations.length === 0" class="col-span-3 text-center py-12">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No recommendations yet</h3>
                <p class="text-gray-600 mb-4">Click "Generate New Recommendations" to get AI-powered product suggestions</p>
            </div>
        </div>
    </div>

    <!-- Trending Products -->
    <div x-show="activeTab === 'trending'">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="rec in trendingProducts" :key="rec.id">
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <span class="inline-flex items-center px-2 py-1 bg-red-100 text-red-800 text-xs font-bold rounded-full mb-2">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
                                </svg>
                                TRENDING
                            </span>
                            <h3 class="text-lg font-semibold text-gray-900" x-text="rec.product?.name"></h3>
                        </div>
                    </div>

                    <div class="mb-4">
                        <p class="text-2xl font-bold text-gray-900" x-text="formatCurrency(rec.product?.price)"></p>
                    </div>

                    <a :href="`/vendor/products/${rec.product_id}`" class="block w-full px-4 py-2 bg-red-600 text-white text-center rounded-lg hover:bg-red-700 text-sm font-medium">
                        View Trending Product
                    </a>
                </div>
            </template>

            <div x-show="trendingProducts.length === 0" class="col-span-3 text-center py-12">
                <p class="text-gray-600">No trending products at the moment</p>
            </div>
        </div>
    </div>

    <!-- Order Predictions -->
    <div x-show="activeTab === 'predictions'">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Predicted Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Confidence</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="pred in predictions" :key="pred.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900" x-text="pred.product?.name"></div>
                                <div class="text-sm text-gray-500" x-text="pred.product?.category"></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="new Date(pred.predicted_date).toLocaleDateString()"></td>
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="pred.predicted_quantity"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-green-600 h-2 rounded-full" :style="`width: ${pred.confidence * 100}%`"></div>
                                    </div>
                                    <span class="text-sm text-gray-900" x-text="(pred.confidence * 100).toFixed(0) + '%'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <a :href="`/vendor/products/${pred.product_id}`" class="text-blue-600 hover:underline text-sm">View Product</a>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="predictions.length === 0">
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            No predictions available. Click "Generate Predictions" to create AI-powered order predictions.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function recommendationsData() {
    return {
        activeTab: 'personalized',
        personalizedRecommendations: [],
        trendingProducts: [],
        predictions: [],
        generating: false,

        async init() {
            await this.loadPersonalizedRecommendations();
            await this.loadTrendingProducts();
            await this.loadPredictions();
        },

        async loadPersonalizedRecommendations() {
            try {
                const data = await api.getPersonalizedRecommendations();
                this.personalizedRecommendations = data.data || [];
            } catch (error) {
                console.error('Failed to load recommendations:', error);
                this.personalizedRecommendations = [];
            }
        },

        async loadTrendingProducts() {
            try {
                const data = await api.getTrendingProducts();
                this.trendingProducts = data.data || [];
            } catch (error) {
                console.error('Failed to load trending products:', error);
                this.trendingProducts = [];
            }
        },

        async loadPredictions() {
            try {
                const data = await api.getOrderPredictions();
                this.predictions = data.data || [];
            } catch (error) {
                console.error('Failed to load predictions:', error);
                this.predictions = [];
            }
        },

        async generateRecommendations() {
            this.generating = true;
            try {
                await api.client.post('/vendor/recommendations/calculate');
                alert('Recommendations generated successfully!');
                await this.loadPersonalizedRecommendations();
                await this.loadTrendingProducts();
            } catch (error) {
                console.error('Failed to generate recommendations:', error);
                alert('Failed to generate recommendations. Please try again.');
            } finally {
                this.generating = false;
            }
        },

        async generatePredictions() {
            this.generating = true;
            try {
                await api.generatePredictions();
                alert('Predictions generated successfully!');
                await this.loadPredictions();
            } catch (error) {
                console.error('Failed to generate predictions:', error);
                alert('Failed to generate predictions. Please try again.');
            } finally {
                this.generating = false;
            }
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

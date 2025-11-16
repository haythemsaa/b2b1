<?php

namespace App\Services;

use App\Models\SearchQuery;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class SearchService
{
    public function search(
        User $vendor,
        string $query,
        ?array $filters = null,
        ?string $context = 'catalog'
    ): array {
        $startTime = microtime(true);
        
        $normalized = SearchQuery::normalize($query);
        
        $results = $this->performSearch($vendor, $normalized, $filters);
        
        $responseTime = (int)((microtime(true) - $startTime) * 1000);
        
        SearchQuery::create([
            'vendor_id' => $vendor->id,
            'user_id' => auth()->id(),
            'query_text' => $query,
            'normalized_query' => $normalized,
            'filters' => $filters,
            'results_count' => $results->count(),
            'has_results' => $results->isNotEmpty(),
            'search_context' => $context,
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'response_time_ms' => $responseTime,
        ]);

        return [
            'query' => $query,
            'results' => $results,
            'count' => $results->count(),
            'response_time_ms' => $responseTime,
        ];
    }

    protected function performSearch(User $vendor, string $query, ?array $filters): Collection
    {
        $queryBuilder = Product::where('vendor_id', $vendor->id)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%");
            });

        if ($filters) {
            if (isset($filters['category'])) {
                $queryBuilder->where('category', $filters['category']);
            }
            if (isset($filters['min_price'])) {
                $queryBuilder->where('price', '>=', $filters['min_price']);
            }
            if (isset($filters['max_price'])) {
                $queryBuilder->where('price', '<=', $filters['max_price']);
            }
        }

        return $queryBuilder->limit(100)->get();
    }

    public function getPopularSearches(User $vendor, int $limit = 10): Collection
    {
        return SearchQuery::forVendor($vendor->id)
            ->where('has_results', true)
            ->groupBy('normalized_query')
            ->selectRaw('normalized_query, COUNT(*) as search_count')
            ->orderBy('search_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getFailedSearches(User $vendor, int $limit = 20): Collection
    {
        return SearchQuery::forVendor($vendor->id)
            ->noResults()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchQuery extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'user_id',
        'query_text',
        'normalized_query',
        'filters',
        'results_count',
        'has_results',
        'clicked_result_position',
        'clicked_product_id',
        'resulted_in_order',
        'search_context',
        'session_id',
        'ip_address',
        'user_agent',
        'response_time_ms',
    ];

    protected $casts = [
        'filters' => 'array',
        'has_results' => 'boolean',
        'resulted_in_order' => 'boolean',
    ];

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get clicked product
     */
    public function clickedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'clicked_product_id');
    }

    /**
     * Record click
     */
    public function recordClick(Product $product, int $position): void
    {
        $this->update([
            'clicked_product_id' => $product->id,
            'clicked_result_position' => $position,
        ]);
    }

    /**
     * Mark resulted in order
     */
    public function markAsOrdered(): void
    {
        $this->update(['resulted_in_order' => true]);
    }

    /**
     * Normalize query
     */
    public static function normalize(string $query): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $query)));
    }

    /**
     * Scope: No results
     */
    public function scopeNoResults($query)
    {
        return $query->where('has_results', false);
    }

    /**
     * Scope: With clicks
     */
    public function scopeWithClicks($query)
    {
        return $query->whereNotNull('clicked_product_id');
    }

    /**
     * Scope: Converted
     */
    public function scopeConverted($query)
    {
        return $query->where('resulted_in_order', true);
    }

    /**
     * Scope: For vendor
     */
    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope: By context
     */
    public function scopeByContext($query, string $context)
    {
        return $query->where('search_context', $context);
    }
}

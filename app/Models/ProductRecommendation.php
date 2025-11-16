<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'product_id',
        'recommended_product_id',
        'recommendation_type',
        'confidence_score',
        'support_count',
        'lift',
        'recommendation_data',
        'is_active',
        'last_calculated_at',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:4',
        'lift' => 'decimal:4',
        'recommendation_data' => 'array',
        'is_active' => 'boolean',
        'last_calculated_at' => 'datetime',
    ];

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get the source product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the recommended product
     */
    public function recommendedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'recommended_product_id');
    }

    /**
     * Check if recommendation is strong
     */
    public function isStrong(): bool
    {
        return $this->confidence_score >= 0.7;
    }

    /**
     * Check if recommendation is weak
     */
    public function isWeak(): bool
    {
        return $this->confidence_score < 0.3;
    }

    /**
     * Scope: Active recommendations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: For product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: By type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('recommendation_type', $type);
    }

    /**
     * Scope: High confidence
     */
    public function scopeHighConfidence($query, float $threshold = 0.7)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    /**
     * Scope: Order by confidence
     */
    public function scopeByConfidence($query)
    {
        return $query->orderBy('confidence_score', 'desc');
    }
}

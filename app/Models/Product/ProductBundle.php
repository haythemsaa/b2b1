<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBundle extends Model
{
    protected $fillable = [
        'bundle_product_id',
        'product_id',
        'quantity',
        'discount_percentage',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'discount_percentage' => 'decimal:2',
    ];

    // Relationships
    public function bundleProduct(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'bundle_product_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    // Methods
    public function getDiscountedPriceAttribute(): float
    {
        if (!$this->product) {
            return 0;
        }

        $basePrice = $this->product->price * $this->quantity;
        $discount = $basePrice * ($this->discount_percentage / 100);

        return round($basePrice - $discount, 2);
    }

    public function getTotalSavingsAttribute(): float
    {
        if (!$this->product) {
            return 0;
        }

        $basePrice = $this->product->price * $this->quantity;
        return round($basePrice - $this->discounted_price, 2);
    }
}

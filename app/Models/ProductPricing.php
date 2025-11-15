<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPricing extends Model
{
    protected $fillable = [
        'product_id',
        'vendor_group_id',
        'vendor_id',
        'price',
        'discount_percentage',
        'min_quantity',
        'max_quantity',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'discount_percentage' => 'decimal:2',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
    ];

    // Relations
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function vendorGroup()
    {
        return $this->belongsTo(VendorGroup::class);
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    // Helper Methods
    public function getFinalPrice(): float
    {
        $price = $this->price;

        if ($this->discount_percentage > 0) {
            $price = $price * (1 - $this->discount_percentage / 100);
        }

        return round($price, 3);
    }

    public function isApplicableForQuantity(int $quantity): bool
    {
        if ($quantity < $this->min_quantity) {
            return false;
        }

        if ($this->max_quantity && $quantity > $this->max_quantity) {
            return false;
        }

        return true;
    }
}

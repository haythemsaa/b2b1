<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionEligibility extends Model
{
    protected $fillable = [
        'promotion_id',
        'product_id',
        'category_id',
        'vendor_group_id',
        'vendor_id',
    ];

    // Relations
    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
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
    public function isApplicableToProduct(int $productId): bool
    {
        if ($this->product_id) {
            return $this->product_id === $productId;
        }

        if ($this->category_id) {
            $product = Product::find($productId);
            return $product && $product->category_id === $this->category_id;
        }

        return true; // Applicable to all products if no specific product/category
    }

    public function isApplicableToVendor(int $vendorId): bool
    {
        if ($this->vendor_id) {
            return $this->vendor_id === $vendorId;
        }

        if ($this->vendor_group_id) {
            $vendor = User::with('vendorProfile')->find($vendorId);
            return $vendor && $vendor->vendorProfile?->vendor_group_id === $this->vendor_group_id;
        }

        return true; // Applicable to all vendors if no specific vendor/group
    }
}

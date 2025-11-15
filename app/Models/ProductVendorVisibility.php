<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVendorVisibility extends Model
{
    protected $fillable = [
        'product_id',
        'vendor_group_id',
        'vendor_id',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
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
}

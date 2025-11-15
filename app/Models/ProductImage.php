<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'path',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relations
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Helper Methods
    public function getUrl(): string
    {
        return asset('storage/' . $this->path);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sku',
        'name_fr',
        'name_ar',
        'description_fr',
        'description_ar',
        'category_id',
        'brand',
        'unit',
        'base_price',
        'stock_quantity',
        'minimum_order_quantity',
        'order_multiple',
        'alert_stock_level',
        'is_active',
        'allow_backorder',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_backorder' => 'boolean',
        'base_price' => 'decimal:3',
        'stock_quantity' => 'integer',
        'minimum_order_quantity' => 'integer',
        'order_multiple' => 'integer',
        'alert_stock_level' => 'integer',
    ];

    // Relations
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function visibility()
    {
        return $this->hasMany(ProductVendorVisibility::class);
    }

    public function pricing()
    {
        return $this->hasMany(ProductPricing::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function promotionEligibility()
    {
        return $this->hasMany(PromotionEligibility::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'alert_stock_level');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Helper Methods
    public function getName(string $locale = 'fr'): string
    {
        return $locale === 'ar' && $this->name_ar
            ? $this->name_ar
            : $this->name_fr;
    }

    public function getDescription(string $locale = 'fr'): ?string
    {
        return $locale === 'ar' && $this->description_ar
            ? $this->description_ar
            : $this->description_fr;
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->alert_stock_level;
    }

    public function canOrder(int $quantity): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($quantity < $this->minimum_order_quantity) {
            return false;
        }

        if ($this->order_multiple > 1 && $quantity % $this->order_multiple !== 0) {
            return false;
        }

        if (!$this->allow_backorder && $quantity > $this->stock_quantity) {
            return false;
        }

        return true;
    }

    public function getAvailableQuantity(): int
    {
        return $this->allow_backorder ? PHP_INT_MAX : $this->stock_quantity;
    }

    public function getPrimaryImageUrl(): ?string
    {
        return $this->primaryImage?->path ?? $this->images->first()?->path;
    }
}

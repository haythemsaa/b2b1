<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'name_fr',
        'name_ar',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Boot method for auto-generating slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name_fr);
            }
        });
    }

    // Relations
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
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

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Helper Methods
    public function getName(string $locale = 'fr'): string
    {
        return $locale === 'ar' && $this->name_ar
            ? $this->name_ar
            : $this->name_fr;
    }

    public function isParent(): bool
    {
        return $this->children()->exists();
    }

    public function getFullPath(string $locale = 'fr'): string
    {
        $path = [$this->getName($locale)];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->getName($locale));
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }
}

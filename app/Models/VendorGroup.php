<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VendorGroup extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'default_discount_percentage',
        'minimum_order_amount',
        'allowed_features',
        'priority_level',
        'is_active',
    ];

    protected $casts = [
        'allowed_features' => 'array',
        'is_active' => 'boolean',
        'default_discount_percentage' => 'decimal:2',
        'minimum_order_amount' => 'decimal:3',
        'priority_level' => 'integer',
    ];

    // Boot method for auto-generating slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($group) {
            if (empty($group->slug)) {
                $group->slug = Str::slug($group->name);
            }
        });
    }

    // Relations
    public function vendorProfiles()
    {
        return $this->hasMany(VendorProfile::class);
    }

    public function vendors()
    {
        return $this->hasManyThrough(User::class, VendorProfile::class, 'vendor_group_id', 'id', 'id', 'user_id');
    }

    public function productVisibility()
    {
        return $this->hasMany(ProductVendorVisibility::class);
    }

    public function productPricing()
    {
        return $this->hasMany(ProductPricing::class);
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

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority_level', 'desc');
    }

    // Helper Methods
    public function hasFeature(string $feature): bool
    {
        $features = $this->allowed_features ?? [];
        return in_array($feature, $features);
    }

    public function getVendorCount(): int
    {
        return $this->vendorProfiles()->count();
    }
}

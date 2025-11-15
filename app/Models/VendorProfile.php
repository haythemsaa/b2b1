<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'company_registration',
        'tax_number',
        'billing_address',
        'shipping_address',
        'city',
        'postal_code',
        'country',
        'vendor_group_id',
        'credit_limit',
        'payment_term',
        'minimum_order_amount',
        'priority_shipping',
        'allowed_features',
    ];

    protected $casts = [
        'allowed_features' => 'array',
        'priority_shipping' => 'boolean',
        'credit_limit' => 'decimal:3',
        'minimum_order_amount' => 'decimal:3',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vendorGroup()
    {
        return $this->belongsTo(VendorGroup::class);
    }

    // Accessors
    public function getMergedFeaturesAttribute(): array
    {
        $groupFeatures = $this->vendorGroup?->allowed_features ?? [];
        $individualFeatures = $this->allowed_features ?? [];

        return array_unique(array_merge($groupFeatures, $individualFeatures));
    }

    public function getFullAddressAttribute(): string
    {
        return sprintf(
            "%s, %s %s, %s",
            $this->billing_address,
            $this->postal_code ?? '',
            $this->city,
            $this->country
        );
    }

    // Helper Methods
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->merged_features);
    }

    public function canPlaceOrder(float $orderTotal): bool
    {
        if ($orderTotal < $this->minimum_order_amount) {
            return false;
        }

        // Check credit limit if applicable
        if ($this->payment_term !== 'immediate' && $this->credit_limit > 0) {
            $currentCredit = $this->user->orders()
                ->whereIn('status', ['pending', 'confirmed', 'processing'])
                ->sum('total');

            return ($currentCredit + $orderTotal) <= $this->credit_limit;
        }

        return true;
    }
}

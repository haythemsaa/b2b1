<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Promotion extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:3',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relations
    public function eligibility()
    {
        return $this->hasMany(PromotionEligibility::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    // Helper Methods
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        return $now->greaterThanOrEqualTo($this->start_date) &&
               $now->lessThanOrEqualTo($this->end_date);
    }

    public function isUpcoming(): bool
    {
        return now()->lessThan($this->start_date);
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->end_date);
    }

    public function calculateDiscount(float $price): float
    {
        if ($this->type === 'percentage') {
            return $price * ($this->value / 100);
        }

        return min($this->value, $price);
    }

    public function applyDiscount(float $price): float
    {
        $discount = $this->calculateDiscount($price);
        return max(0, $price - $discount);
    }

    public function getDaysRemaining(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->end_date);
    }
}

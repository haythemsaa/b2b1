<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vendor_id',
        'preference_key',
        'preference_value',
        'preference_category',
        'description',
        'is_system_generated',
    ];

    protected $casts = [
        'preference_value' => 'array',
        'is_system_generated' => 'boolean',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get preference value
     */
    public function getValue(?string $key = null)
    {
        if ($key === null) {
            return $this->preference_value;
        }

        return $this->preference_value[$key] ?? null;
    }

    /**
     * Set preference value
     */
    public function setValue($value, ?string $key = null): void
    {
        if ($key === null) {
            $this->preference_value = $value;
        } else {
            $current = $this->preference_value ?? [];
            $current[$key] = $value;
            $this->preference_value = $current;
        }

        $this->save();
    }

    /**
     * Get or create preference
     */
    public static function getOrCreate(
        int $userId,
        string $key,
        $defaultValue = null,
        ?int $vendorId = null,
        ?string $category = null
    ): self {
        return static::firstOrCreate(
            [
                'user_id' => $userId,
                'vendor_id' => $vendorId,
                'preference_key' => $key,
            ],
            [
                'preference_value' => $defaultValue ?? [],
                'preference_category' => $category,
            ]
        );
    }

    /**
     * Scope: For user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: For vendor
     */
    public function scopeForVendor($query, ?int $vendorId)
    {
        if ($vendorId) {
            return $query->where('vendor_id', $vendorId);
        }
        return $query;
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('preference_category', $category);
    }

    /**
     * Scope: By key
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('preference_key', $key);
    }
}

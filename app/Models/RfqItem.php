<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'rfq_id',
        'product_id',
        'product_sku',
        'product_name',
        'description',
        'quantity_requested',
        'unit',
        'specifications',
        'quoted_unit_price',
        'quoted_subtotal',
        'vendor_notes',
    ];

    protected $casts = [
        'specifications' => 'array',
        'quoted_unit_price' => 'decimal:3',
        'quoted_subtotal' => 'decimal:3',
    ];

    // Relationships
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeQuoted($query)
    {
        return $query->whereNotNull('quoted_unit_price');
    }

    public function scopeWithProduct($query)
    {
        return $query->whereNotNull('product_id');
    }

    public function scopeCustom($query)
    {
        return $query->whereNull('product_id');
    }

    // Helper methods
    public function isQuoted(): bool
    {
        return $this->quoted_unit_price !== null;
    }

    public function isCustomItem(): bool
    {
        return $this->product_id === null;
    }

    public function hasSpecifications(): bool
    {
        return !empty($this->specifications);
    }

    public function setQuote(float $unitPrice, ?string $notes = null): void
    {
        $this->update([
            'quoted_unit_price' => $unitPrice,
            'quoted_subtotal' => $unitPrice * $this->quantity_requested,
            'vendor_notes' => $notes,
        ]);
    }

    public function getFormattedQuantity(): string
    {
        return $this->quantity_requested . ' ' . $this->unit;
    }

    public function getEstimatedTotal(): ?float
    {
        if (!$this->quoted_unit_price) {
            return null;
        }

        return (float) $this->quoted_subtotal;
    }
}

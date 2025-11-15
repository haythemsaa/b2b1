<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_sku',
        'product_name',
        'quantity',
        'unit_price',
        'discount_percentage',
        'discount_amount',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:3',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:3',
        'subtotal' => 'decimal:3',
    ];

    // Relations
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function returnItems()
    {
        return $this->hasMany(ReturnItem::class);
    }

    // Helper Methods
    public function getTotalPrice(): float
    {
        return $this->subtotal;
    }

    public function getDiscountedUnitPrice(): float
    {
        $price = $this->unit_price;

        if ($this->discount_percentage > 0) {
            $price = $price * (1 - $this->discount_percentage / 100);
        }

        if ($this->discount_amount > 0) {
            $price = max(0, $price - $this->discount_amount);
        }

        return round($price, 3);
    }

    public function canBeReturned(): bool
    {
        if ($this->order->status !== 'delivered') {
            return false;
        }

        $alreadyReturned = $this->returnItems()->sum('quantity');
        return $this->quantity > $alreadyReturned;
    }

    public function getRemainingQuantityForReturn(): int
    {
        $alreadyReturned = $this->returnItems()->sum('quantity');
        return max(0, $this->quantity - $alreadyReturned);
    }
}

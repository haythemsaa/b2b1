<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    protected $fillable = [
        'return_request_id',
        'order_item_id',
        'quantity',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    // Relations
    public function returnRequest()
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    // Helper Methods
    public function getProduct()
    {
        return $this->orderItem->product;
    }

    public function getTotalValue(): float
    {
        return $this->quantity * $this->orderItem->unit_price;
    }
}

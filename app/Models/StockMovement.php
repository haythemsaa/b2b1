<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
    ];

    // Relations
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeIncoming($query)
    {
        return $query->whereIn('type', ['in', 'released']);
    }

    public function scopeOutgoing($query)
    {
        return $query->whereIn('type', ['out', 'reserved']);
    }

    // Helper Methods
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'in' => 'Entrée en stock',
            'out' => 'Sortie de stock',
            'adjustment' => 'Ajustement',
            'reserved' => 'Réservé',
            'released' => 'Libéré',
            default => $this->type
        };
    }
}

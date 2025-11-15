<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'vendor_id',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_cost',
        'total',
        'shipping_address',
        'vendor_notes',
        'admin_notes',
        'tracking_number',
        'carrier',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
        'is_priority',
    ];

    protected $casts = [
        'subtotal' => 'decimal:3',
        'discount_amount' => 'decimal:3',
        'tax_amount' => 'decimal:3',
        'shipping_cost' => 'decimal:3',
        'total' => 'decimal:3',
        'is_priority' => 'boolean',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Relations
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function returnRequests()
    {
        return $this->hasMany(ReturnRequest::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopePriority($query)
    {
        return $query->where('is_priority', true);
    }

    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    // Helper Methods
    public function updateStatus(string $newStatus, ?string $notes = null): void
    {
        $this->update([
            'status' => $newStatus,
            'admin_notes' => $notes ? ($this->admin_notes ? $this->admin_notes . "\n" . $notes : $notes) : $this->admin_notes
        ]);

        // Fire event for notification
        event(new \App\Events\OrderStatusUpdated($this));
    }

    public function markAsShipped(string $carrier, string $trackingNumber): void
    {
        $this->update([
            'status' => 'shipped',
            'carrier' => $carrier,
            'tracking_number' => $trackingNumber,
            'shipped_at' => now()
        ]);

        event(new \App\Events\OrderShipped($this));
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now()
        ]);
    }

    public function cancel(string $reason): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now()
        ]);

        // Release reserved stock
        foreach ($this->items as $item) {
            // This will be handled by StockService
        }
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    public function canBeReturned(): bool
    {
        return $this->status === 'delivered';
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'processing' => 'En préparation',
            'shipped' => 'Expédiée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
            default => $this->status
        };
    }
}

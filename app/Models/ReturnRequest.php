<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    protected $fillable = [
        'rma_number',
        'order_id',
        'vendor_id',
        'status',
        'type',
        'reason',
        'vendor_comments',
        'admin_response',
        'approved_at',
        'rejected_at',
        'completed_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relations
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function items()
    {
        return $this->hasMany(ReturnItem::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Helper Methods
    public function approve(?string $adminResponse = null): void
    {
        $this->update([
            'status' => 'approved',
            'admin_response' => $adminResponse,
            'approved_at' => now(),
        ]);
    }

    public function reject(string $adminResponse): void
    {
        $this->update([
            'status' => 'rejected',
            'admin_response' => $adminResponse,
            'rejected_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeRejected(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'approved';
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvée',
            'rejected' => 'Refusée',
            'completed' => 'Terminée',
            default => $this->status
        };
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'refund' => 'Remboursement',
            'exchange' => 'Échange',
            'credit' => 'Avoir',
            default => $this->type
        };
    }
}

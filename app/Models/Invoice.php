<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'order_id',
        'vendor_id',
        'invoice_date',
        'due_date',
        'paid_date',
        'subtotal',
        'tax',
        'total',
        'paid_amount',
        'payment_terms',
        'early_payment_discount',
        'early_payment_days',
        'early_payment_deadline',
        'status',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'early_payment_deadline' => 'date',
        'subtotal' => 'decimal:3',
        'tax' => 'decimal:3',
        'total' => 'decimal:3',
        'paid_amount' => 'decimal:3',
        'early_payment_discount' => 'decimal:2',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(PaymentReminder::class);
    }

    // Scopes
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where('due_date', '<', now());
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    // Helper methods
    public function getRemainingAmount(): float
    {
        return (float) ($this->total - $this->paid_amount);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid' || $this->paid_amount >= $this->total;
    }

    public function isOverdue(): bool
    {
        return !$this->isPaid()
            && $this->status !== 'cancelled'
            && $this->due_date->isPast();
    }

    public function isEligibleForEarlyDiscount(): bool
    {
        return $this->early_payment_discount > 0
            && $this->early_payment_deadline
            && now()->lte($this->early_payment_deadline);
    }

    public function getEarlyDiscountAmount(): float
    {
        if (!$this->isEligibleForEarlyDiscount()) {
            return 0;
        }

        return (float) ($this->total * ($this->early_payment_discount / 100));
    }

    public function getAmountWithEarlyDiscount(): float
    {
        return $this->total - $this->getEarlyDiscountAmount();
    }

    public function getDaysUntilDue(): int
    {
        return now()->diffInDays($this->due_date, false);
    }

    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return $this->due_date->diffInDays(now());
    }

    public function recordPayment(float $amount, string $method, ?string $reference = null, ?string $notes = null): InvoicePayment
    {
        $payment = $this->payments()->create([
            'payment_number' => $this->generatePaymentNumber(),
            'payment_date' => now(),
            'amount' => $amount,
            'payment_method' => $method,
            'transaction_reference' => $reference,
            'notes' => $notes,
        ]);

        $this->updatePaymentStatus();

        return $payment;
    }

    public function updatePaymentStatus(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        $this->paid_amount = $totalPaid;

        if ($totalPaid >= $this->total) {
            $this->status = 'paid';
            $this->paid_date = now();
        } elseif ($totalPaid > 0) {
            $this->status = 'partial';
        } elseif ($this->isOverdue()) {
            $this->status = 'overdue';
        } else {
            $this->status = 'pending';
        }

        $this->save();
    }

    public function markAsOverdue(): void
    {
        if ($this->isOverdue() && $this->status !== 'paid' && $this->status !== 'cancelled') {
            $this->status = 'overdue';
            $this->save();
        }
    }

    public function cancel(?string $reason = null): void
    {
        $this->status = 'cancelled';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n\n" : '') . "Cancelled: " . $reason;
        }
        $this->save();
    }

    protected function generatePaymentNumber(): string
    {
        $year = now()->year;
        $lastPayment = InvoicePayment::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastPayment ? ((int) substr($lastPayment->payment_number, -5)) + 1 : 1;

        return sprintf('PAY-%d-%05d', $year, $sequence);
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $lastInvoice = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, -5)) + 1 : 1;

        return sprintf('INV-%d-%05d', $year, $sequence);
    }
}

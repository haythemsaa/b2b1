<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'payment_number',
        'payment_date',
        'amount',
        'payment_method',
        'transaction_reference',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:3',
    ];

    // Relationships
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // Scopes
    public function scopeForInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    // Helper methods
    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 3) . ' TND';
    }

    public function getFormattedMethod(): string
    {
        return match($this->payment_method) {
            'bank_transfer' => 'Bank Transfer',
            'check' => 'Check',
            'cash' => 'Cash',
            'card' => 'Card',
            'other' => 'Other',
            default => ucfirst($this->payment_method),
        };
    }

    // Events
    protected static function booted(): void
    {
        static::created(function (InvoicePayment $payment) {
            $payment->invoice->updatePaymentStatus();
        });

        static::deleted(function (InvoicePayment $payment) {
            $payment->invoice->updatePaymentStatus();
        });
    }
}

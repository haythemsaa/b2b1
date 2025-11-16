<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'reminder_type',
        'reminder_date',
        'days_until_due',
        'sent_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'reminder_date' => 'date',
        'sent_at' => 'datetime',
    ];

    // Relationships
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeDueToday($query)
    {
        return $query->where('reminder_date', now()->toDateString())
            ->where('status', 'pending');
    }

    public function scopeForInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('reminder_type', $type);
    }

    // Helper methods
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }

    public function getFormattedType(): string
    {
        return match($this->reminder_type) {
            'before_due_7' => '7 days before due date',
            'before_due_3' => '3 days before due date',
            'on_due' => 'On due date',
            'overdue_7' => '7 days overdue',
            'overdue_15' => '15 days overdue',
            'overdue_30' => '30 days overdue',
            default => ucfirst(str_replace('_', ' ', $this->reminder_type)),
        };
    }

    public function getUrgencyLevel(): string
    {
        return match($this->reminder_type) {
            'before_due_7', 'before_due_3' => 'info',
            'on_due' => 'warning',
            'overdue_7', 'overdue_15', 'overdue_30' => 'urgent',
            default => 'normal',
        };
    }

    public function shouldSend(): bool
    {
        return $this->status === 'pending'
            && $this->reminder_date->isToday()
            && !$this->invoice->isPaid()
            && $this->invoice->status !== 'cancelled';
    }

    public static function createForInvoice(Invoice $invoice): void
    {
        $reminders = [];

        // Before due reminders
        if ($invoice->due_date->isFuture()) {
            // 7 days before
            $reminderDate = $invoice->due_date->copy()->subDays(7);
            if ($reminderDate->isFuture()) {
                $reminders[] = [
                    'invoice_id' => $invoice->id,
                    'reminder_type' => 'before_due_7',
                    'reminder_date' => $reminderDate,
                    'days_until_due' => 7,
                    'status' => 'pending',
                ];
            }

            // 3 days before
            $reminderDate = $invoice->due_date->copy()->subDays(3);
            if ($reminderDate->isFuture()) {
                $reminders[] = [
                    'invoice_id' => $invoice->id,
                    'reminder_type' => 'before_due_3',
                    'reminder_date' => $reminderDate,
                    'days_until_due' => 3,
                    'status' => 'pending',
                ];
            }
        }

        // On due date
        $reminders[] = [
            'invoice_id' => $invoice->id,
            'reminder_type' => 'on_due',
            'reminder_date' => $invoice->due_date,
            'days_until_due' => 0,
            'status' => 'pending',
        ];

        // Overdue reminders
        $reminders[] = [
            'invoice_id' => $invoice->id,
            'reminder_type' => 'overdue_7',
            'reminder_date' => $invoice->due_date->copy()->addDays(7),
            'days_until_due' => -7,
            'status' => 'pending',
        ];

        $reminders[] = [
            'invoice_id' => $invoice->id,
            'reminder_type' => 'overdue_15',
            'reminder_date' => $invoice->due_date->copy()->addDays(15),
            'days_until_due' => -15,
            'status' => 'pending',
        ];

        $reminders[] = [
            'invoice_id' => $invoice->id,
            'reminder_type' => 'overdue_30',
            'reminder_date' => $invoice->due_date->copy()->addDays(30),
            'days_until_due' => -30,
            'status' => 'pending',
        ];

        foreach ($reminders as $reminder) {
            static::create($reminder);
        }
    }
}

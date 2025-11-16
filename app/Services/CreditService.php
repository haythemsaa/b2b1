<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\PaymentReminder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditService
{
    /**
     * Check if vendor has available credit for a given amount
     */
    public function hasAvailableCredit(User $vendor, float $amount): bool
    {
        $profile = $vendor->vendorProfile;

        if (!$profile) {
            return false;
        }

        // If vendor is on credit hold, deny
        if ($profile->credit_hold) {
            return false;
        }

        // If credit limit is 0, assume unlimited (or immediate payment only)
        if ($profile->credit_limit == 0) {
            return $profile->payment_terms === 'immediate';
        }

        $availableCredit = $this->getAvailableCredit($vendor);

        return $availableCredit >= $amount;
    }

    /**
     * Get available credit for vendor
     */
    public function getAvailableCredit(User $vendor): float
    {
        $profile = $vendor->vendorProfile;

        if (!$profile) {
            return 0;
        }

        return max(0, $profile->credit_limit - $profile->credit_used);
    }

    /**
     * Get credit usage statistics for vendor
     */
    public function getCreditStats(User $vendor): array
    {
        $profile = $vendor->vendorProfile;

        if (!$profile) {
            return [
                'credit_limit' => 0,
                'credit_used' => 0,
                'available_credit' => 0,
                'credit_utilization' => 0,
                'is_on_hold' => false,
                'hold_reason' => null,
            ];
        }

        $available = $this->getAvailableCredit($vendor);
        $utilization = $profile->credit_limit > 0
            ? ($profile->credit_used / $profile->credit_limit) * 100
            : 0;

        return [
            'credit_limit' => (float) $profile->credit_limit,
            'credit_used' => (float) $profile->credit_used,
            'available_credit' => $available,
            'credit_utilization' => round($utilization, 2),
            'is_on_hold' => (bool) $profile->credit_hold,
            'hold_reason' => $profile->credit_hold_reason,
            'payment_terms' => $profile->payment_terms,
        ];
    }

    /**
     * Reserve credit for an order (when invoice is created)
     */
    public function reserveCredit(User $vendor, float $amount): bool
    {
        return DB::transaction(function () use ($vendor, $amount) {
            $profile = $vendor->vendorProfile()->lockForUpdate()->first();

            if (!$profile) {
                throw new \Exception('Vendor profile not found');
            }

            if (!$this->hasAvailableCredit($vendor, $amount)) {
                return false;
            }

            $profile->credit_used += $amount;
            $profile->save();

            // Check if credit limit reached and should be put on hold
            if ($profile->credit_used >= $profile->credit_limit) {
                $this->putOnCreditHold($vendor, 'Credit limit reached');
            }

            Log::info('Credit reserved', [
                'vendor_id' => $vendor->id,
                'amount' => $amount,
                'new_credit_used' => $profile->credit_used,
            ]);

            return true;
        });
    }

    /**
     * Release credit (when invoice is paid or cancelled)
     */
    public function releaseCredit(User $vendor, float $amount): void
    {
        DB::transaction(function () use ($vendor, $amount) {
            $profile = $vendor->vendorProfile()->lockForUpdate()->first();

            if (!$profile) {
                throw new \Exception('Vendor profile not found');
            }

            $profile->credit_used = max(0, $profile->credit_used - $amount);
            $profile->save();

            // If credit was on hold due to limit, check if we can remove hold
            if ($profile->credit_hold && $profile->credit_used < $profile->credit_limit) {
                if (strpos($profile->credit_hold_reason, 'Credit limit reached') !== false) {
                    $this->removeFromCreditHold($vendor);
                }
            }

            Log::info('Credit released', [
                'vendor_id' => $vendor->id,
                'amount' => $amount,
                'new_credit_used' => $profile->credit_used,
            ]);
        });
    }

    /**
     * Put vendor on credit hold
     */
    public function putOnCreditHold(User $vendor, string $reason): void
    {
        $profile = $vendor->vendorProfile;

        if (!$profile) {
            throw new \Exception('Vendor profile not found');
        }

        $profile->update([
            'credit_hold' => true,
            'credit_hold_reason' => $reason,
        ]);

        Log::warning('Vendor put on credit hold', [
            'vendor_id' => $vendor->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Remove vendor from credit hold
     */
    public function removeFromCreditHold(User $vendor): void
    {
        $profile = $vendor->vendorProfile;

        if (!$profile) {
            throw new \Exception('Vendor profile not found');
        }

        $profile->update([
            'credit_hold' => false,
            'credit_hold_reason' => null,
        ]);

        Log::info('Vendor removed from credit hold', [
            'vendor_id' => $vendor->id,
        ]);
    }

    /**
     * Create invoice from order
     */
    public function createInvoiceFromOrder(Order $order): Invoice
    {
        return DB::transaction(function () use ($order) {
            $vendor = $order->vendor;
            $profile = $vendor->vendorProfile;

            if (!$profile) {
                throw new \Exception('Vendor profile not found');
            }

            // Calculate due date based on payment terms
            $dueDate = $this->calculateDueDate($profile->payment_terms);

            // Calculate early payment deadline if applicable
            $earlyPaymentDeadline = null;
            if ($profile->early_payment_discount > 0 && $profile->early_payment_days > 0) {
                $earlyPaymentDeadline = now()->addDays($profile->early_payment_days);
            }

            // Reserve credit if not immediate payment
            if ($profile->payment_terms !== 'immediate') {
                if (!$this->reserveCredit($vendor, $order->total)) {
                    throw new \Exception('Insufficient credit available');
                }
            }

            // Create invoice
            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'order_id' => $order->id,
                'vendor_id' => $vendor->id,
                'invoice_date' => now(),
                'due_date' => $dueDate,
                'subtotal' => $order->subtotal,
                'tax' => $order->tax,
                'total' => $order->total,
                'payment_terms' => $profile->payment_terms,
                'early_payment_discount' => $profile->early_payment_discount,
                'early_payment_days' => $profile->early_payment_days,
                'early_payment_deadline' => $earlyPaymentDeadline,
                'status' => 'pending',
            ]);

            // Create payment reminders
            PaymentReminder::createForInvoice($invoice);

            Log::info('Invoice created from order', [
                'invoice_id' => $invoice->id,
                'order_id' => $order->id,
                'vendor_id' => $vendor->id,
                'total' => $invoice->total,
            ]);

            return $invoice;
        });
    }

    /**
     * Calculate due date based on payment terms
     */
    public function calculateDueDate(string $paymentTerms): \Carbon\Carbon
    {
        return match($paymentTerms) {
            'immediate' => now(),
            'net_15' => now()->addDays(15),
            'net_30' => now()->addDays(30),
            'net_60' => now()->addDays(60),
            'net_90' => now()->addDays(90),
            default => now()->addDays(30),
        };
    }

    /**
     * Process invoice payment
     */
    public function processPayment(
        Invoice $invoice,
        float $amount,
        string $method,
        ?string $reference = null,
        ?string $notes = null
    ): void {
        DB::transaction(function () use ($invoice, $amount, $method, $reference, $notes) {
            // Record the payment
            $payment = $invoice->recordPayment($amount, $method, $reference, $notes);

            // If fully paid, release the credit
            if ($invoice->isPaid()) {
                $this->releaseCredit($invoice->vendor, $invoice->total);
            }

            Log::info('Payment processed', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'method' => $method,
            ]);
        });
    }

    /**
     * Mark overdue invoices
     */
    public function markOverdueInvoices(): int
    {
        $count = 0;

        Invoice::overdue()->chunk(100, function ($invoices) use (&$count) {
            foreach ($invoices as $invoice) {
                $invoice->markAsOverdue();
                $count++;
            }
        });

        Log::info('Marked overdue invoices', ['count' => $count]);

        return $count;
    }

    /**
     * Get overdue invoices for vendor
     */
    public function getOverdueInvoices(User $vendor)
    {
        return Invoice::forVendor($vendor->id)
            ->overdue()
            ->with(['order', 'payments'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Get upcoming invoices (due within X days)
     */
    public function getUpcomingInvoices(User $vendor, int $daysAhead = 7)
    {
        return Invoice::forVendor($vendor->id)
            ->pending()
            ->whereBetween('due_date', [now(), now()->addDays($daysAhead)])
            ->with(['order', 'payments'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Calculate early payment savings
     */
    public function calculateEarlyPaymentSavings(Invoice $invoice): array
    {
        if (!$invoice->isEligibleForEarlyDiscount()) {
            return [
                'eligible' => false,
                'discount_percentage' => 0,
                'discount_amount' => 0,
                'amount_to_pay' => $invoice->total,
                'savings' => 0,
                'deadline' => null,
            ];
        }

        $discountAmount = $invoice->getEarlyDiscountAmount();
        $amountToPay = $invoice->getAmountWithEarlyDiscount();

        return [
            'eligible' => true,
            'discount_percentage' => (float) $invoice->early_payment_discount,
            'discount_amount' => $discountAmount,
            'amount_to_pay' => $amountToPay,
            'savings' => $discountAmount,
            'deadline' => $invoice->early_payment_deadline,
            'days_remaining' => $invoice->early_payment_deadline->diffInDays(now()),
        ];
    }

    /**
     * Update vendor credit limit (admin function)
     */
    public function updateCreditLimit(User $vendor, float $newLimit, ?string $reason = null): void
    {
        $profile = $vendor->vendorProfile;

        if (!$profile) {
            throw new \Exception('Vendor profile not found');
        }

        $oldLimit = $profile->credit_limit;

        $profile->update([
            'credit_limit' => $newLimit,
        ]);

        // If new limit is less than current usage, put on hold
        if ($newLimit < $profile->credit_used) {
            $this->putOnCreditHold($vendor, 'Credit limit reduced below current usage');
        }

        Log::info('Credit limit updated', [
            'vendor_id' => $vendor->id,
            'old_limit' => $oldLimit,
            'new_limit' => $newLimit,
            'reason' => $reason,
        ]);
    }

    /**
     * Recalculate vendor credit usage from invoices
     */
    public function recalculateCreditUsage(User $vendor): float
    {
        $profile = $vendor->vendorProfile;

        if (!$profile) {
            throw new \Exception('Vendor profile not found');
        }

        // Sum all unpaid invoice amounts
        $creditUsed = Invoice::forVendor($vendor->id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum('total');

        $profile->update([
            'credit_used' => $creditUsed,
        ]);

        Log::info('Credit usage recalculated', [
            'vendor_id' => $vendor->id,
            'credit_used' => $creditUsed,
        ]);

        return (float) $creditUsed;
    }
}

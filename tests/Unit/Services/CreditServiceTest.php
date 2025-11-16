<?php

namespace Tests\Unit\Services;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Order;
use App\Models\User;
use App\Models\VendorGroup;
use App\Models\VendorProfile;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CreditService $creditService;
    protected User $vendor;
    protected VendorProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creditService = new CreditService();

        // Create vendor with credit profile
        $group = VendorGroup::factory()->create();
        $this->vendor = User::factory()->create(['role' => 'vendor']);
        $this->profile = VendorProfile::factory()->create([
            'user_id' => $this->vendor->id,
            'vendor_group_id' => $group->id,
            'credit_limit' => 10000.000,
            'credit_used' => 0,
            'payment_terms' => 'net_30',
            'early_payment_discount' => 2.00,
            'early_payment_days' => 10,
        ]);
    }

    /** @test */
    public function it_checks_available_credit()
    {
        $this->assertTrue($this->creditService->hasAvailableCredit($this->vendor, 5000));
        $this->assertTrue($this->creditService->hasAvailableCredit($this->vendor, 10000));
        $this->assertFalse($this->creditService->hasAvailableCredit($this->vendor, 10001));
    }

    /** @test */
    public function it_gets_credit_stats()
    {
        $stats = $this->creditService->getCreditStats($this->vendor);

        $this->assertEquals(10000, $stats['credit_limit']);
        $this->assertEquals(0, $stats['credit_used']);
        $this->assertEquals(10000, $stats['available_credit']);
        $this->assertEquals(0, $stats['credit_utilization']);
        $this->assertFalse($stats['is_on_hold']);
    }

    /** @test */
    public function it_reserves_credit()
    {
        $result = $this->creditService->reserveCredit($this->vendor, 3000);

        $this->assertTrue($result);
        $this->profile->refresh();
        $this->assertEquals(3000, $this->profile->credit_used);
    }

    /** @test */
    public function it_cannot_reserve_more_than_limit()
    {
        $result = $this->creditService->reserveCredit($this->vendor, 15000);

        $this->assertFalse($result);
        $this->profile->refresh();
        $this->assertEquals(0, $this->profile->credit_used);
    }

    /** @test */
    public function it_releases_credit()
    {
        // Reserve some credit first
        $this->creditService->reserveCredit($this->vendor, 5000);
        $this->profile->refresh();
        $this->assertEquals(5000, $this->profile->credit_used);

        // Release it
        $this->creditService->releaseCredit($this->vendor, 2000);
        $this->profile->refresh();
        $this->assertEquals(3000, $this->profile->credit_used);
    }

    /** @test */
    public function it_puts_vendor_on_credit_hold()
    {
        $this->creditService->putOnCreditHold($this->vendor, 'Test reason');

        $this->profile->refresh();
        $this->assertTrue($this->profile->credit_hold);
        $this->assertEquals('Test reason', $this->profile->credit_hold_reason);
    }

    /** @test */
    public function it_removes_vendor_from_credit_hold()
    {
        $this->profile->update([
            'credit_hold' => true,
            'credit_hold_reason' => 'Test',
        ]);

        $this->creditService->removeFromCreditHold($this->vendor);

        $this->profile->refresh();
        $this->assertFalse($this->profile->credit_hold);
        $this->assertNull($this->profile->credit_hold_reason);
    }

    /** @test */
    public function it_denies_credit_when_on_hold()
    {
        $this->profile->update(['credit_hold' => true]);

        $this->assertFalse($this->creditService->hasAvailableCredit($this->vendor, 1000));
    }

    /** @test */
    public function it_creates_invoice_from_order()
    {
        $order = Order::factory()->create([
            'vendor_id' => $this->vendor->id,
            'subtotal' => 1000.000,
            'tax' => 190.000,
            'total' => 1190.000,
        ]);

        $invoice = $this->creditService->createInvoiceFromOrder($order);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($order->id, $invoice->order_id);
        $this->assertEquals($this->vendor->id, $invoice->vendor_id);
        $this->assertEquals(1000.000, $invoice->subtotal);
        $this->assertEquals(190.000, $invoice->tax);
        $this->assertEquals(1190.000, $invoice->total);
        $this->assertEquals('net_30', $invoice->payment_terms);
        $this->assertEquals('pending', $invoice->status);

        // Credit should be reserved
        $this->profile->refresh();
        $this->assertEquals(1190.000, $this->profile->credit_used);
    }

    /** @test */
    public function it_calculates_due_date_correctly()
    {
        $this->assertEquals(
            now()->addDays(15)->toDateString(),
            $this->creditService->calculateDueDate('net_15')->toDateString()
        );

        $this->assertEquals(
            now()->addDays(30)->toDateString(),
            $this->creditService->calculateDueDate('net_30')->toDateString()
        );

        $this->assertEquals(
            now()->addDays(60)->toDateString(),
            $this->creditService->calculateDueDate('net_60')->toDateString()
        );
    }

    /** @test */
    public function it_processes_invoice_payment()
    {
        $invoice = Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
            'total' => 1000.000,
            'status' => 'pending',
        ]);

        // Reserve credit
        $this->creditService->reserveCredit($this->vendor, 1000);

        // Process payment
        $this->creditService->processPayment($invoice, 1000, 'bank_transfer', 'REF123');

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(1000.000, $invoice->paid_amount);

        // Credit should be released
        $this->profile->refresh();
        $this->assertEquals(0, $this->profile->credit_used);
    }

    /** @test */
    public function it_handles_partial_payments()
    {
        $invoice = Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
            'total' => 1000.000,
            'status' => 'pending',
        ]);

        // Reserve credit
        $this->creditService->reserveCredit($this->vendor, 1000);

        // Process partial payment
        $this->creditService->processPayment($invoice, 400, 'bank_transfer');

        $invoice->refresh();
        $this->assertEquals('partial', $invoice->status);
        $this->assertEquals(400.000, $invoice->paid_amount);
        $this->assertEquals(600.000, $invoice->getRemainingAmount());

        // Credit should still be reserved (not fully paid)
        $this->profile->refresh();
        $this->assertEquals(1000.000, $this->profile->credit_used);
    }

    /** @test */
    public function it_calculates_early_payment_savings()
    {
        $invoice = Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
            'total' => 1000.000,
            'early_payment_discount' => 2.00,
            'early_payment_deadline' => now()->addDays(5),
        ]);

        $savings = $this->creditService->calculateEarlyPaymentSavings($invoice);

        $this->assertTrue($savings['eligible']);
        $this->assertEquals(2.00, $savings['discount_percentage']);
        $this->assertEquals(20.000, $savings['discount_amount']);
        $this->assertEquals(980.000, $savings['amount_to_pay']);
    }

    /** @test */
    public function it_updates_credit_limit()
    {
        $this->creditService->updateCreditLimit($this->vendor, 15000, 'Increased limit');

        $this->profile->refresh();
        $this->assertEquals(15000, $this->profile->credit_limit);
    }

    /** @test */
    public function it_recalculates_credit_usage()
    {
        // Create some invoices
        Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
            'total' => 1000.000,
            'status' => 'pending',
        ]);

        Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
            'total' => 500.000,
            'status' => 'partial',
        ]);

        Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
            'total' => 800.000,
            'status' => 'paid', // Should not be counted
        ]);

        $creditUsed = $this->creditService->recalculateCreditUsage($this->vendor);

        $this->assertEquals(1500.000, $creditUsed);
        $this->profile->refresh();
        $this->assertEquals(1500.000, $this->profile->credit_used);
    }
}

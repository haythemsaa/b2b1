<?php

namespace Tests\Feature\Api\Vendor;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Order;
use App\Models\User;
use App\Models\VendorGroup;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendor;
    protected VendorProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create vendor
        $group = VendorGroup::factory()->create();
        $this->vendor = User::factory()->create(['role' => 'vendor']);
        $this->profile = VendorProfile::factory()->create([
            'user_id' => $this->vendor->id,
            'vendor_group_id' => $group->id,
            'credit_limit' => 10000.000,
            'credit_used' => 0,
            'payment_terms' => 'net_30',
        ]);
    }

    /** @test */
    public function vendor_can_list_their_invoices()
    {
        Sanctum::actingAs($this->vendor);

        // Create invoices
        Invoice::factory()->count(3)->create([
            'vendor_id' => $this->vendor->id,
        ]);

        $response = $this->getJson('/api/vendor/invoices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        '*' => ['id', 'invoice_number', 'total', 'status'],
                    ],
                ],
            ]);
    }

    /** @test */
    public function vendor_can_filter_invoices_by_status()
    {
        Sanctum::actingAs($this->vendor);

        Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'pending',
        ]);

        Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'paid',
        ]);

        $response = $this->getJson('/api/vendor/invoices?status=pending');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('pending', $data[0]['status']);
    }

    /** @test */
    public function vendor_can_view_specific_invoice()
    {
        Sanctum::actingAs($this->vendor);

        $invoice = Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        $response = $this->getJson("/api/vendor/invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'invoice' => ['id', 'invoice_number', 'total', 'status'],
                    'early_payment_info',
                ],
            ]);
    }

    /** @test */
    public function vendor_cannot_view_another_vendors_invoice()
    {
        Sanctum::actingAs($this->vendor);

        $otherVendor = User::factory()->create(['role' => 'vendor']);
        $invoice = Invoice::factory()->create([
            'vendor_id' => $otherVendor->id,
        ]);

        $response = $this->getJson("/api/vendor/invoices/{$invoice->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function vendor_can_make_payment_on_invoice()
    {
        Sanctum::actingAs($this->vendor);

        $invoice = Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
            'total' => 1000.000,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/vendor/invoices/{$invoice->id}/payment", [
            'amount' => 500.000,
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'REF123',
            'notes' => 'Partial payment',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Payment recorded successfully',
            ]);

        $invoice->refresh();
        $this->assertEquals(500.000, $invoice->paid_amount);
        $this->assertEquals('partial', $invoice->status);
    }

    /** @test */
    public function payment_amount_cannot_exceed_remaining_balance()
    {
        Sanctum::actingAs($this->vendor);

        $invoice = Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
            'total' => 1000.000,
            'paid_amount' => 600.000,
        ]);

        $response = $this->postJson("/api/vendor/invoices/{$invoice->id}/payment", [
            'amount' => 500.000, // More than remaining 400
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Payment amount exceeds remaining balance',
            ]);
    }

    /** @test */
    public function vendor_can_get_credit_stats()
    {
        Sanctum::actingAs($this->vendor);

        $response = $this->getJson('/api/vendor/invoices/credit-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'credit' => [
                        'credit_limit',
                        'credit_used',
                        'available_credit',
                        'credit_utilization',
                        'is_on_hold',
                    ],
                    'overdue_invoices',
                    'upcoming_invoices',
                ],
            ]);
    }

    /** @test */
    public function vendor_can_get_overdue_invoices()
    {
        Sanctum::actingAs($this->vendor);

        Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
            'due_date' => now()->subDays(5),
            'status' => 'pending',
        ]);

        Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
            'due_date' => now()->addDays(5),
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/vendor/invoices/overdue');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /** @test */
    public function vendor_can_get_upcoming_invoices()
    {
        Sanctum::actingAs($this->vendor);

        Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
            'due_date' => now()->addDays(3),
            'status' => 'pending',
        ]);

        Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
            'due_date' => now()->addDays(20),
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/vendor/invoices/upcoming?days=7');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /** @test */
    public function vendor_can_get_payment_history()
    {
        Sanctum::actingAs($this->vendor);

        $invoice = Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        InvoicePayment::factory()->count(3)->create([
            'invoice_id' => $invoice->id,
        ]);

        $response = $this->getJson('/api/vendor/invoices/payment-history');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    /** @test */
    public function payment_requires_valid_amount()
    {
        Sanctum::actingAs($this->vendor);

        $invoice = Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        $response = $this->postJson("/api/vendor/invoices/{$invoice->id}/payment", [
            'amount' => -100, // Invalid
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function payment_requires_valid_method()
    {
        Sanctum::actingAs($this->vendor);

        $invoice = Invoice::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        $response = $this->postJson("/api/vendor/invoices/{$invoice->id}/payment", [
            'amount' => 100,
            'payment_method' => 'invalid_method',
        ]);

        $response->assertStatus(422);
    }
}

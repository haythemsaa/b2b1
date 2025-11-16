<?php

namespace Tests\Unit\Services;

use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\RfqQuote;
use App\Models\User;
use App\Models\VendorGroup;
use App\Models\VendorProfile;
use App\Models\Product;
use App\Services\RfqService;
use App\Services\Catalog\CatalogService;
use App\Services\Order\OrderService;
use App\Services\Pricing\PricingService;
use App\Services\Stock\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfqServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RfqService $rfqService;
    protected User $vendor;
    protected User $admin;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rfqService = new RfqService();

        // Create vendor
        $group = VendorGroup::factory()->create();
        $this->vendor = User::factory()->create(['role' => 'vendor']);
        VendorProfile::factory()->create([
            'user_id' => $this->vendor->id,
            'vendor_group_id' => $group->id,
        ]);

        // Create admin
        $this->admin = User::factory()->create(['role' => 'admin']);

        // Create product
        $this->product = Product::factory()->create([
            'sku' => 'TEST-001',
            'name' => 'Test Product',
            'base_price' => 100.000,
        ]);
    }

    /** @test */
    public function it_creates_rfq()
    {
        $items = [
            [
                'product_id' => $this->product->id,
                'sku' => $this->product->sku,
                'name' => $this->product->name,
                'quantity' => 100,
                'unit' => 'pcs',
            ],
        ];

        $rfq = $this->rfqService->createRfq(
            $this->vendor,
            'Test RFQ',
            $items,
            'Test description',
            5000.000,
            now()->addDays(30)->toDateString(),
            'high',
            30
        );

        $this->assertInstanceOf(Rfq::class, $rfq);
        $this->assertEquals('Test RFQ', $rfq->title);
        $this->assertEquals($this->vendor->id, $rfq->vendor_id);
        $this->assertEquals('draft', $rfq->status);
        $this->assertCount(1, $rfq->items);
    }

    /** @test */
    public function it_submits_rfq()
    {
        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'draft',
        ]);

        RfqItem::factory()->create(['rfq_id' => $rfq->id]);

        $this->rfqService->submitRfq($rfq);

        $rfq->refresh();
        $this->assertEquals('submitted', $rfq->status);
        $this->assertNotNull($rfq->submitted_at);
    }

    /** @test */
    public function it_cannot_submit_empty_rfq()
    {
        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'draft',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('RFQ must have at least one item');

        $this->rfqService->submitRfq($rfq);
    }

    /** @test */
    public function it_creates_quote_for_rfq()
    {
        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'submitted',
        ]);

        $item = RfqItem::factory()->create([
            'rfq_id' => $rfq->id,
            'quantity_requested' => 100,
        ]);

        $items = [
            [
                'id' => $item->id,
                'unit_price' => 50.000,
                'notes' => 'Bulk discount applied',
            ],
        ];

        $quote = $this->rfqService->createQuote(
            $rfq,
            $this->admin,
            $items,
            'net_30',
            15,
            30
        );

        $this->assertInstanceOf(RfqQuote::class, $quote);
        $this->assertEquals($rfq->id, $quote->rfq_id);
        $this->assertEquals($this->admin->id, $quote->quoted_by);
        $this->assertEquals('draft', $quote->status);

        // Check item was quoted
        $item->refresh();
        $this->assertEquals(50.000, $item->quoted_unit_price);
        $this->assertEquals(5000.000, $item->quoted_subtotal);
    }

    /** @test */
    public function it_sends_quote_to_vendor()
    {
        $quote = RfqQuote::factory()->create([
            'status' => 'draft',
        ]);

        $this->rfqService->sendQuote($quote);

        $quote->refresh();
        $this->assertEquals('sent', $quote->status);
        $this->assertNotNull($quote->sent_at);

        // RFQ status should be updated
        $this->assertEquals('quoted', $quote->rfq->status);
    }

    /** @test */
    public function it_accepts_quote()
    {
        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'quoted',
        ]);

        $quote = RfqQuote::factory()->create([
            'rfq_id' => $rfq->id,
            'status' => 'sent',
            'valid_until' => now()->addDays(10),
        ]);

        $this->rfqService->acceptQuote($rfq, $quote, $this->vendor);

        $rfq->refresh();
        $quote->refresh();

        $this->assertEquals('accepted', $rfq->status);
        $this->assertEquals('accepted', $quote->status);
        $this->assertNotNull($quote->accepted_at);
    }

    /** @test */
    public function it_rejects_quote()
    {
        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'quoted',
        ]);

        $quote = RfqQuote::factory()->create([
            'rfq_id' => $rfq->id,
            'status' => 'sent',
        ]);

        $this->rfqService->rejectQuote($rfq, $quote, $this->vendor, 'Price too high');

        $rfq->refresh();
        $quote->refresh();

        $this->assertEquals('rejected', $rfq->status);
        $this->assertEquals('rejected', $quote->status);
        $this->assertEquals('Price too high', $quote->rejection_reason);
    }

    /** @test */
    public function it_adds_negotiation_message()
    {
        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'quoted',
        ]);

        $negotiation = $this->rfqService->addNegotiation(
            $rfq,
            $this->vendor,
            'Can you offer a better price?',
            true,
            4500.000,
            'net_30',
            20
        );

        $this->assertDatabaseHas('rfq_negotiations', [
            'rfq_id' => $rfq->id,
            'user_id' => $this->vendor->id,
            'message' => 'Can you offer a better price?',
            'is_counter_offer' => true,
            'proposed_price' => 4500.000,
        ]);

        // RFQ status should change to negotiating
        $rfq->refresh();
        $this->assertEquals('negotiating', $rfq->status);
    }

    /** @test */
    public function it_converts_accepted_rfq_to_order()
    {
        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'accepted',
        ]);

        $item = RfqItem::factory()->create([
            'rfq_id' => $rfq->id,
            'product_id' => $this->product->id,
            'product_sku' => $this->product->sku,
            'product_name' => $this->product->name,
            'quantity_requested' => 10,
            'quoted_unit_price' => 100.000,
            'quoted_subtotal' => 1000.000,
        ]);

        $quote = RfqQuote::factory()->create([
            'rfq_id' => $rfq->id,
            'status' => 'sent',
            'subtotal' => 1000.000,
            'tax' => 190.000,
            'total' => 1190.000,
        ]);

        $order = $this->rfqService->convertToOrder($rfq);

        $this->assertEquals($this->vendor->id, $order->vendor_id);
        $this->assertEquals(1000.000, $order->subtotal);
        $this->assertEquals(190.000, $order->tax);
        $this->assertEquals(1190.000, $order->total);
        $this->assertCount(1, $order->items);

        // RFQ status should be converted
        $rfq->refresh();
        $this->assertEquals('converted', $rfq->status);
    }

    /** @test */
    public function it_cannot_convert_non_accepted_rfq()
    {
        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'submitted', // Not accepted
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only accepted RFQs can be converted to orders');

        $this->rfqService->convertToOrder($rfq);
    }

    /** @test */
    public function it_cancels_rfq()
    {
        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'submitted',
        ]);

        $this->rfqService->cancelRfq($rfq, $this->vendor, 'Changed requirements');

        $rfq->refresh();
        $this->assertEquals('rejected', $rfq->status);
    }

    /** @test */
    public function it_gets_vendor_stats()
    {
        // Create various RFQs
        Rfq::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'draft']);
        Rfq::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'submitted']);
        Rfq::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'quoted']);
        Rfq::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'accepted']);

        $stats = $this->rfqService->getVendorStats($this->vendor);

        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(1, $stats['draft']);
        $this->assertEquals(1, $stats['submitted']);
        $this->assertEquals(1, $stats['quoted']);
        $this->assertEquals(1, $stats['accepted']);
    }
}

<?php

namespace Tests\Feature\Api\Vendor;

use App\Models\Product;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\RfqQuote;
use App\Models\User;
use App\Models\VendorGroup;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RfqApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendor;
    protected User $admin;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

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
        ]);
    }

    /** @test */
    public function vendor_can_create_rfq()
    {
        Sanctum::actingAs($this->vendor);

        $response = $this->postJson('/api/vendor/rfqs', [
            'title' => 'Bulk Order Request',
            'description' => 'Need bulk pricing for 1000 units',
            'target_budget' => 50000.000,
            'required_delivery_date' => now()->addDays(60)->toDateString(),
            'priority' => 'high',
            'expires_in_days' => 30,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'sku' => $this->product->sku,
                    'name' => $this->product->name,
                    'quantity' => 1000,
                    'unit' => 'pcs',
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'rfq_number', 'title', 'status', 'items'],
            ]);

        $this->assertDatabaseHas('rfqs', [
            'vendor_id' => $this->vendor->id,
            'title' => 'Bulk Order Request',
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function vendor_can_list_their_rfqs()
    {
        Sanctum::actingAs($this->vendor);

        Rfq::factory()->count(3)->create([
            'vendor_id' => $this->vendor->id,
        ]);

        $response = $this->getJson('/api/vendor/rfqs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'rfqs' => [
                        'data' => [
                            '*' => ['id', 'rfq_number', 'title', 'status'],
                        ],
                    ],
                    'stats',
                ],
            ]);
    }

    /** @test */
    public function vendor_can_view_specific_rfq()
    {
        Sanctum::actingAs($this->vendor);

        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        RfqItem::factory()->create(['rfq_id' => $rfq->id]);

        $response = $this->getJson("/api/vendor/rfqs/{$rfq->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'rfq' => ['id', 'rfq_number', 'title', 'items'],
                    'unread_count',
                ],
            ]);
    }

    /** @test */
    public function vendor_cannot_view_another_vendors_rfq()
    {
        Sanctum::actingAs($this->vendor);

        $otherVendor = User::factory()->create(['role' => 'vendor']);
        $rfq = Rfq::factory()->create([
            'vendor_id' => $otherVendor->id,
        ]);

        $response = $this->getJson("/api/vendor/rfqs/{$rfq->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function vendor_can_update_draft_rfq()
    {
        Sanctum::actingAs($this->vendor);

        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'draft',
            'title' => 'Original Title',
        ]);

        $response = $this->putJson("/api/vendor/rfqs/{$rfq->id}", [
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200);

        $rfq->refresh();
        $this->assertEquals('Updated Title', $rfq->title);
    }

    /** @test */
    public function vendor_cannot_update_submitted_rfq()
    {
        Sanctum::actingAs($this->vendor);

        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'submitted',
        ]);

        $response = $this->putJson("/api/vendor/rfqs/{$rfq->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function vendor_can_submit_rfq()
    {
        Sanctum::actingAs($this->vendor);

        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'draft',
        ]);

        RfqItem::factory()->create(['rfq_id' => $rfq->id]);

        $response = $this->postJson("/api/vendor/rfqs/{$rfq->id}/submit");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'RFQ submitted successfully',
            ]);

        $rfq->refresh();
        $this->assertEquals('submitted', $rfq->status);
    }

    /** @test */
    public function vendor_can_accept_quote()
    {
        Sanctum::actingAs($this->vendor);

        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'quoted',
        ]);

        $quote = RfqQuote::factory()->create([
            'rfq_id' => $rfq->id,
            'status' => 'sent',
            'valid_until' => now()->addDays(10),
        ]);

        $response = $this->postJson("/api/vendor/rfqs/{$rfq->id}/quotes/{$quote->id}/accept");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Quote accepted successfully',
            ]);

        $rfq->refresh();
        $this->assertEquals('accepted', $rfq->status);
    }

    /** @test */
    public function vendor_can_reject_quote()
    {
        Sanctum::actingAs($this->vendor);

        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'quoted',
        ]);

        $quote = RfqQuote::factory()->create([
            'rfq_id' => $rfq->id,
            'status' => 'sent',
        ]);

        $response = $this->postJson("/api/vendor/rfqs/{$rfq->id}/quotes/{$quote->id}/reject", [
            'reason' => 'Price is too high',
        ]);

        $response->assertStatus(200);

        $rfq->refresh();
        $this->assertEquals('rejected', $rfq->status);
    }

    /** @test */
    public function vendor_can_add_negotiation_message()
    {
        Sanctum::actingAs($this->vendor);

        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'quoted',
        ]);

        $response = $this->postJson("/api/vendor/rfqs/{$rfq->id}/negotiations", [
            'message' => 'Can you provide a better price?',
            'is_counter_offer' => true,
            'proposed_price' => 4500.000,
            'proposed_terms' => 'net_30',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Message added successfully',
            ]);

        $this->assertDatabaseHas('rfq_negotiations', [
            'rfq_id' => $rfq->id,
            'user_id' => $this->vendor->id,
            'message' => 'Can you provide a better price?',
            'is_counter_offer' => true,
        ]);
    }

    /** @test */
    public function vendor_can_view_negotiations()
    {
        Sanctum::actingAs($this->vendor);

        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        // Create some negotiations
        $rfq->negotiations()->create([
            'user_id' => $this->vendor->id,
            'message' => 'Message from vendor',
        ]);

        $rfq->negotiations()->create([
            'user_id' => $this->admin->id,
            'message' => 'Message from admin',
            'is_read' => false,
        ]);

        $response = $this->getJson("/api/vendor/rfqs/{$rfq->id}/negotiations");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => ['id', 'message', 'user_id'],
                ],
            ]);

        // Unread messages should be marked as read
        $this->assertDatabaseHas('rfq_negotiations', [
            'rfq_id' => $rfq->id,
            'user_id' => $this->admin->id,
            'is_read' => true,
        ]);
    }

    /** @test */
    public function vendor_can_cancel_rfq()
    {
        Sanctum::actingAs($this->vendor);

        $rfq = Rfq::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'submitted',
        ]);

        $response = $this->postJson("/api/vendor/rfqs/{$rfq->id}/cancel", [
            'reason' => 'Requirements changed',
        ]);

        $response->assertStatus(200);

        $rfq->refresh();
        $this->assertEquals('rejected', $rfq->status);
    }

    /** @test */
    public function rfq_creation_validates_required_fields()
    {
        Sanctum::actingAs($this->vendor);

        $response = $this->postJson('/api/vendor/rfqs', [
            'title' => '', // Missing required field
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function rfq_creation_requires_at_least_one_item()
    {
        Sanctum::actingAs($this->vendor);

        $response = $this->postJson('/api/vendor/rfqs', [
            'title' => 'Test RFQ',
            'priority' => 'medium',
            'items' => [], // Empty items
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function vendor_can_convert_accepted_rfq_to_order()
    {
        Sanctum::actingAs($this->vendor);

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

        $response = $this->postJson("/api/vendor/rfqs/{$rfq->id}/convert-to-order");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'order' => ['id', 'order_number', 'total'],
                    'rfq',
                ],
            ]);

        $rfq->refresh();
        $this->assertEquals('converted', $rfq->status);
    }
}

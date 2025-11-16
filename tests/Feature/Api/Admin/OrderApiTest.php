<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $vendor;
    protected Order $order;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->vendor = User::factory()->create(['role' => 'vendor', 'status' => 'active']);
        VendorProfile::factory()->create(['user_id' => $this->vendor->id]);

        $this->product = Product::factory()->create([
            'stock_quantity' => 500,
            'is_active' => true,
        ]);

        $this->order = Order::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'pending',
        ]);

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);
    }

    /** @test */
    public function admin_can_list_all_orders()
    {
        Order::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_number', 'vendor', 'status', 'total', 'created_at'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    /** @test */
    public function vendor_cannot_access_admin_orders_endpoint()
    {
        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/admin/orders');

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_orders()
    {
        $response = $this->getJson('/api/admin/orders');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_can_view_specific_order()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/orders/{$this->order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ])
            ->assertJsonStructure([
                'items' => [
                    '*' => ['product_id', 'quantity', 'unit_price'],
                ],
            ]);
    }

    /** @test */
    public function admin_can_confirm_pending_order()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/orders/{$this->order->id}/confirm", [
                'notes' => 'Order confirmed by admin',
            ]);

        $response->assertStatus(200);

        $this->order->refresh();
        $this->assertEquals('confirmed', $this->order->status);
        $this->assertStringContainsString('Order confirmed by admin', $this->order->admin_notes);
    }

    /** @test */
    public function admin_cannot_confirm_non_pending_order()
    {
        $this->order->update(['status' => 'delivered']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/orders/{$this->order->id}/confirm");

        $response->assertStatus(422);
    }

    /** @test */
    public function admin_can_start_processing_order()
    {
        $this->order->update(['status' => 'confirmed']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/orders/{$this->order->id}/start-processing", [
                'notes' => 'Started preparing order',
            ]);

        $response->assertStatus(200);

        $this->order->refresh();
        $this->assertEquals('processing', $this->order->status);
    }

    /** @test */
    public function admin_can_ship_order()
    {
        $this->order->update(['status' => 'processing']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/orders/{$this->order->id}/ship", [
                'tracking_number' => 'TRACK123456',
                'carrier' => 'DHL Express',
                'notes' => 'Shipped via DHL',
            ]);

        $response->assertStatus(200);

        $this->order->refresh();
        $this->assertEquals('shipped', $this->order->status);
        $this->assertStringContainsString('TRACK123456', $this->order->admin_notes);

        // Verify stock was deducted
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'out',
            'quantity' => 10,
        ]);
    }

    /** @test */
    public function admin_can_deliver_order()
    {
        $this->order->update(['status' => 'shipped']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/orders/{$this->order->id}/deliver", [
                'notes' => 'Delivered successfully',
            ]);

        $response->assertStatus(200);

        $this->order->refresh();
        $this->assertEquals('delivered', $this->order->status);
    }

    /** @test */
    public function admin_can_cancel_order()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/orders/{$this->order->id}/cancel", [
                'reason' => 'Product out of stock',
            ]);

        $response->assertStatus(200);

        $this->order->refresh();
        $this->assertEquals('cancelled', $this->order->status);

        // Verify stock was released
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'released',
        ]);
    }

    /** @test */
    public function admin_cannot_cancel_delivered_order()
    {
        $this->order->update(['status' => 'delivered']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/orders/{$this->order->id}/cancel", [
                'reason' => 'Test',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function admin_can_update_order_notes()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/orders/{$this->order->id}/notes", [
                'admin_notes' => 'Special handling required',
            ]);

        $response->assertStatus(200);

        $this->order->refresh();
        $this->assertEquals('Special handling required', $this->order->admin_notes);
    }

    /** @test */
    public function admin_can_filter_orders_by_status()
    {
        Order::factory()->create(['status' => 'pending']);
        Order::factory()->create(['status' => 'confirmed']);
        Order::factory()->create(['status' => 'delivered']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/orders?status=pending');

        $response->assertStatus(200);

        $orders = $response->json('data');
        foreach ($orders as $order) {
            $this->assertEquals('pending', $order['status']);
        }
    }

    /** @test */
    public function admin_can_filter_orders_by_vendor()
    {
        $vendor2 = User::factory()->create(['role' => 'vendor']);

        Order::factory()->count(3)->create(['vendor_id' => $this->vendor->id]);
        Order::factory()->count(2)->create(['vendor_id' => $vendor2->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/orders?vendor_id={$this->vendor->id}");

        $response->assertStatus(200);

        $orders = $response->json('data');
        foreach ($orders as $order) {
            $this->assertEquals($this->vendor->id, $order['vendor']['id']);
        }
    }

    /** @test */
    public function admin_can_filter_orders_by_date_range()
    {
        Order::factory()->create(['created_at' => now()->subDays(10)]);
        Order::factory()->create(['created_at' => now()->subDays(2)]);
        Order::factory()->create(['created_at' => now()->subDays(1)]);

        $fromDate = now()->subDays(3)->format('Y-m-d');
        $toDate = now()->format('Y-m-d');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/orders?from_date={$fromDate}&to_date={$toDate}");

        $response->assertStatus(200);

        $this->assertEquals(2, count($response->json('data')));
    }

    /** @test */
    public function admin_can_search_orders_by_order_number()
    {
        $order = Order::factory()->create(['order_number' => 'ORD-SPECIAL-999']);
        Order::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/orders?search=SPECIAL');

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertEquals(1, count($orders));
        $this->assertEquals('ORD-SPECIAL-999', $orders[0]['order_number']);
    }

    /** @test */
    public function admin_can_filter_priority_orders()
    {
        Order::factory()->create(['is_priority' => true]);
        Order::factory()->create(['is_priority' => true]);
        Order::factory()->create(['is_priority' => false]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/orders?is_priority=1');

        $response->assertStatus(200);

        $orders = $response->json('data');
        foreach ($orders as $order) {
            $this->assertTrue($order['is_priority']);
        }
    }

    /** @test */
    public function admin_can_get_order_statistics()
    {
        Order::factory()->create(['status' => 'pending', 'total' => 500.000]);
        Order::factory()->create(['status' => 'confirmed', 'total' => 300.000]);
        Order::factory()->create(['status' => 'delivered', 'total' => 1000.000]);
        Order::factory()->create(['status' => 'delivered', 'total' => 1500.000]);
        Order::factory()->create(['status' => 'cancelled', 'total' => 200.000]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/orders/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_orders',
                'pending_orders',
                'confirmed_orders',
                'processing_orders',
                'shipped_orders',
                'delivered_orders',
                'cancelled_orders',
                'total_revenue',
                'average_order_value',
            ]);

        $stats = $response->json();
        $this->assertGreaterThanOrEqual(5, $stats['total_orders']);
        $this->assertEquals(2, $stats['delivered_orders']);
        $this->assertEquals(2500.000, $stats['total_revenue']);
    }

    /** @test */
    public function admin_can_filter_stats_by_date_range()
    {
        Order::factory()->create([
            'status' => 'delivered',
            'total' => 1000.000,
            'created_at' => now()->subDays(10),
        ]);

        Order::factory()->create([
            'status' => 'delivered',
            'total' => 2000.000,
            'created_at' => now()->subDays(2),
        ]);

        $fromDate = now()->subDays(5)->format('Y-m-d');
        $toDate = now()->format('Y-m-d');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/orders/stats?from_date={$fromDate}&to_date={$toDate}");

        $response->assertStatus(200);

        $stats = $response->json();
        $this->assertEquals(2000.000, $stats['total_revenue']);
    }

    /** @test */
    public function admin_can_paginate_orders()
    {
        Order::factory()->count(50)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/orders?per_page=15');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);

        $this->assertEquals(15, count($response->json('data')));
    }

    /** @test */
    public function shipping_order_requires_tracking_number()
    {
        $this->order->update(['status' => 'processing']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/orders/{$this->order->id}/ship", [
                'carrier' => 'DHL',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tracking_number']);
    }

    /** @test */
    public function order_status_transitions_follow_business_rules()
    {
        // Cannot go from pending directly to shipped
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/orders/{$this->order->id}/ship", [
                'tracking_number' => 'TEST123',
            ]);

        $response->assertStatus(422);

        // Must follow: pending → confirmed → processing → shipped
        $this->order->refresh();
        $this->assertEquals('pending', $this->order->status);
    }
}

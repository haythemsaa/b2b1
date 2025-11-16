<?php

namespace Tests\Feature\Api\Vendor;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\VendorProfile;
use App\Models\VendorGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendor;
    protected VendorGroup $vendorGroup;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendorGroup = VendorGroup::factory()->create([
            'name' => 'Standard',
            'discount_percentage' => 0,
            'minimum_order_amount' => 100.000,
        ]);

        $this->vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);

        VendorProfile::factory()->create([
            'user_id' => $this->vendor->id,
            'vendor_group_id' => $this->vendorGroup->id,
            'credit_limit' => 10000.000,
            'current_balance' => 0,
        ]);

        $this->product = Product::factory()->create([
            'base_price' => 100.000,
            'stock_quantity' => 500,
            'is_active' => true,
            'minimum_order_quantity' => 1,
            'order_multiple' => 1,
        ]);
    }

    /** @test */
    public function vendor_can_list_their_orders()
    {
        Order::factory()->count(3)->create(['vendor_id' => $this->vendor->id]);
        Order::factory()->count(2)->create(); // Other vendors

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_number', 'status', 'total', 'created_at'],
                ],
            ]);

        $this->assertEquals(3, count($response->json('data')));
    }

    /** @test */
    public function unauthenticated_user_cannot_access_orders()
    {
        $response = $this->getJson('/api/vendor/orders');

        $response->assertStatus(401);
    }

    /** @test */
    public function vendor_can_create_order()
    {
        $cartItems = [
            ['product_id' => $this->product->id, 'quantity' => 5],
        ];

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/orders', [
                'cart_items' => $cartItems,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'order_number',
                'status',
                'total',
                'items',
            ]);

        $this->assertDatabaseHas('orders', [
            'vendor_id' => $this->vendor->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function order_creation_requires_cart_items()
    {
        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cart_items']);
    }

    /** @test */
    public function order_creation_validates_minimum_order_amount()
    {
        $this->vendorGroup->update(['minimum_order_amount' => 1000.000]);

        $cartItems = [
            ['product_id' => $this->product->id, 'quantity' => 1], // Total 100 TND
        ];

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/orders', [
                'cart_items' => $cartItems,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function vendor_can_view_specific_order()
    {
        $order = Order::factory()->create([
            'vendor_id' => $this->vendor->id,
            'order_number' => 'ORD-123',
        ]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson("/api/vendor/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $order->id,
                'order_number' => 'ORD-123',
            ]);
    }

    /** @test */
    public function vendor_cannot_view_other_vendor_order()
    {
        $otherVendor = User::factory()->create(['role' => 'vendor']);
        $order = Order::factory()->create(['vendor_id' => $otherVendor->id]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson("/api/vendor/orders/{$order->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function vendor_can_cancel_pending_order()
    {
        $order = Order::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'pending',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
        ]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson("/api/vendor/orders/{$order->id}/cancel", [
                'reason' => 'Changed mind',
            ]);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
    }

    /** @test */
    public function vendor_cannot_cancel_shipped_order()
    {
        $order = Order::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'shipped',
        ]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson("/api/vendor/orders/{$order->id}/cancel", [
                'reason' => 'Test',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function vendor_can_filter_orders_by_status()
    {
        Order::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'pending']);
        Order::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'pending']);
        Order::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'delivered']);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/orders?status=pending');

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertEquals(2, count($orders));

        foreach ($orders as $order) {
            $this->assertEquals('pending', $order['status']);
        }
    }

    /** @test */
    public function vendor_can_search_orders()
    {
        $order = Order::factory()->create([
            'vendor_id' => $this->vendor->id,
            'order_number' => 'ORD-SPECIAL-123',
        ]);

        Order::factory()->count(5)->create(['vendor_id' => $this->vendor->id]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/orders?search=SPECIAL');

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertEquals(1, count($orders));
        $this->assertEquals('ORD-SPECIAL-123', $orders[0]['order_number']);
    }

    /** @test */
    public function vendor_can_get_order_statistics()
    {
        Order::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'pending', 'total' => 500.000]);
        Order::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'delivered', 'total' => 1000.000]);
        Order::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'cancelled', 'total' => 300.000]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/orders/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_orders',
                'pending_orders',
                'processing_orders',
                'delivered_orders',
                'cancelled_orders',
                'total_spent',
            ]);
    }

    /** @test */
    public function vendor_can_validate_cart_before_ordering()
    {
        $cartItems = [
            ['product_id' => $this->product->id, 'quantity' => 5],
        ];

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/cart/validate', [
                'cart_items' => $cartItems,
            ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => true]);
    }

    /** @test */
    public function cart_validation_detects_invalid_products()
    {
        $inactiveProduct = Product::factory()->create(['is_active' => false]);

        $cartItems = [
            ['product_id' => $inactiveProduct->id, 'quantity' => 1],
            ['product_id' => 99999, 'quantity' => 1], // Non-existent
        ];

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/cart/validate', [
                'cart_items' => $cartItems,
            ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => false])
            ->assertJsonStructure(['errors']);
    }

    /** @test */
    public function vendor_can_calculate_cart_total()
    {
        $cartItems = [
            ['product_id' => $this->product->id, 'quantity' => 5],
        ];

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/cart/calculate', [
                'cart_items' => $cartItems,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'subtotal',
                'total',
                'total_promotion_discount',
                'meets_minimum',
                'items',
            ]);
    }

    /** @test */
    public function vendor_can_paginate_orders()
    {
        Order::factory()->count(30)->create(['vendor_id' => $this->vendor->id]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/orders?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);

        $this->assertEquals(10, count($response->json('data')));
    }

    /** @test */
    public function vendor_can_filter_orders_by_date_range()
    {
        Order::factory()->create([
            'vendor_id' => $this->vendor->id,
            'created_at' => now()->subDays(10),
        ]);

        Order::factory()->create([
            'vendor_id' => $this->vendor->id,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/orders?from_date=' . now()->subDays(5)->format('Y-m-d') . '&to_date=' . now()->format('Y-m-d'));

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertEquals(1, count($orders));
    }

    /** @test */
    public function inactive_vendor_cannot_create_orders()
    {
        $this->vendor->update(['status' => 'inactive']);

        $cartItems = [
            ['product_id' => $this->product->id, 'quantity' => 1],
        ];

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/orders', [
                'cart_items' => $cartItems,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function order_validates_minimum_quantity()
    {
        $this->product->update(['minimum_order_quantity' => 10]);

        $cartItems = [
            ['product_id' => $this->product->id, 'quantity' => 5],
        ];

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/cart/validate', [
                'cart_items' => $cartItems,
            ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => false]);
    }

    /** @test */
    public function order_validates_order_multiple()
    {
        $this->product->update(['order_multiple' => 5]);

        $cartItems = [
            ['product_id' => $this->product->id, 'quantity' => 7],
        ];

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/cart/validate', [
                'cart_items' => $cartItems,
            ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => false]);
    }

    /** @test */
    public function order_validates_stock_availability()
    {
        $this->product->update(['stock_quantity' => 10, 'allow_backorder' => false]);

        $cartItems = [
            ['product_id' => $this->product->id, 'quantity' => 20],
        ];

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/cart/validate', [
                'cart_items' => $cartItems,
            ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => false]);
    }
}

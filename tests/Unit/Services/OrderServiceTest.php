<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorGroup;
use App\Services\Order\OrderService;
use App\Services\Pricing\PricingService;
use App\Services\Inventory\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;
    protected PricingService $pricingService;
    protected StockService $stockService;
    protected User $vendor;
    protected User $admin;
    protected Product $product;
    protected VendorGroup $vendorGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pricingService = app(PricingService::class);
        $this->stockService = app(StockService::class);
        $this->orderService = new OrderService($this->pricingService, $this->stockService);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

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
            'sku' => 'PROD-001',
            'base_price' => 100.000,
            'stock_quantity' => 500,
            'minimum_order_quantity' => 1,
            'order_multiple' => 1,
            'allow_backorder' => false,
        ]);

        $this->actingAs($this->admin);
    }

    /** @test */
    public function it_can_create_order_from_cart()
    {
        $cartItems = [
            ['product_id' => $this->product->id, 'quantity' => 5],
        ];

        $order = $this->orderService->createOrder($this->vendor, $cartItems);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'vendor_id' => $this->vendor->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
        ]);

        // Check stock was reserved
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'reserved',
            'quantity' => 5,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);
    }

    /** @test */
    public function it_throws_exception_when_non_vendor_creates_order()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User must be a vendor');

        $cartItems = [['product_id' => $this->product->id, 'quantity' => 1]];

        $this->orderService->createOrder($this->admin, $cartItems);
    }

    /** @test */
    public function it_throws_exception_when_order_below_minimum()
    {
        $this->vendorGroup->update(['minimum_order_amount' => 1000.000]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('does not meet minimum order amount');

        $cartItems = [['product_id' => $this->product->id, 'quantity' => 1]];

        $this->orderService->createOrder($this->vendor, $cartItems);
    }

    /** @test */
    public function it_can_update_order_status_to_confirmed()
    {
        $order = Order::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'pending',
        ]);

        $updatedOrder = $this->orderService->updateOrderStatus($order, 'confirmed', 'Confirmed by admin');

        $this->assertEquals('confirmed', $updatedOrder->status);
        $this->assertStringContainsString('Confirmed by admin', $updatedOrder->admin_notes);
    }

    /** @test */
    public function it_throws_exception_on_invalid_status_transition()
    {
        $order = Order::factory()->create([
            'status' => 'pending',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition');

        $this->orderService->updateOrderStatus($order, 'shipped');
    }

    /** @test */
    public function it_can_cancel_order_and_release_stock()
    {
        $order = Order::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'pending',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);

        // Reserve stock first
        $this->stockService->reserveStock($this->product, 10, $order);

        $cancelledOrder = $this->orderService->cancelOrder($order, 'Customer request', true);

        $this->assertEquals('cancelled', $cancelledOrder->status);

        // Check stock was released
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'released',
            'quantity' => 10,
        ]);
    }

    /** @test */
    public function it_can_confirm_order()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $confirmed = $this->orderService->confirmOrder($order);

        $this->assertEquals('confirmed', $confirmed->status);
    }

    /** @test */
    public function it_throws_exception_when_confirming_non_pending_order()
    {
        $order = Order::factory()->create(['status' => 'confirmed']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only pending orders can be confirmed');

        $this->orderService->confirmOrder($order);
    }

    /** @test */
    public function it_can_start_processing_order()
    {
        $order = Order::factory()->create(['status' => 'confirmed']);

        $processing = $this->orderService->startProcessing($order);

        $this->assertEquals('processing', $processing->status);
    }

    /** @test */
    public function it_throws_exception_when_processing_non_confirmed_order()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only confirmed orders can be processed');

        $this->orderService->startProcessing($order);
    }

    /** @test */
    public function it_can_ship_order_and_deduct_stock()
    {
        $order = Order::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'processing',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 15,
        ]);

        $shippingData = [
            'tracking_number' => 'TRACK123',
            'carrier' => 'DHL',
        ];

        $initialStock = $this->product->stock_quantity;

        $shipped = $this->orderService->shipOrder($order, $shippingData);

        $this->assertEquals('shipped', $shipped->status);

        // Check stock was deducted
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'out',
            'quantity' => 15,
        ]);

        $this->product->refresh();
        $this->assertEquals($initialStock - 15, $this->product->stock_quantity);
    }

    /** @test */
    public function it_throws_exception_when_shipping_non_processing_order()
    {
        $order = Order::factory()->create(['status' => 'confirmed']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only processing orders can be shipped');

        $this->orderService->shipOrder($order, []);
    }

    /** @test */
    public function it_can_deliver_order()
    {
        $order = Order::factory()->create(['status' => 'shipped']);

        $delivered = $this->orderService->deliverOrder($order);

        $this->assertEquals('delivered', $delivered->status);
    }

    /** @test */
    public function it_throws_exception_when_delivering_non_shipped_order()
    {
        $order = Order::factory()->create(['status' => 'processing']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only shipped orders can be marked as delivered');

        $this->orderService->deliverOrder($order);
    }

    /** @test */
    public function it_can_get_vendor_orders()
    {
        Order::factory()->count(3)->create(['vendor_id' => $this->vendor->id]);
        Order::factory()->count(2)->create(); // Other vendors

        $orders = $this->orderService->getVendorOrders($this->vendor)->get();

        $this->assertEquals(3, $orders->count());
    }

    /** @test */
    public function it_can_filter_vendor_orders_by_status()
    {
        Order::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'pending']);
        Order::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'pending']);
        Order::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'delivered']);

        $orders = $this->orderService->getVendorOrders($this->vendor, ['status' => 'pending'])->get();

        $this->assertEquals(2, $orders->count());
    }

    /** @test */
    public function it_can_get_all_orders_for_admin()
    {
        Order::factory()->count(5)->create();

        $orders = $this->orderService->getAllOrders()->get();

        $this->assertEquals(5, $orders->count());
    }

    /** @test */
    public function it_can_filter_admin_orders_by_vendor()
    {
        Order::factory()->count(2)->create(['vendor_id' => $this->vendor->id]);
        Order::factory()->count(3)->create();

        $orders = $this->orderService->getAllOrders(['vendor_id' => $this->vendor->id])->get();

        $this->assertEquals(2, $orders->count());
    }

    /** @test */
    public function it_can_get_vendor_order_statistics()
    {
        Order::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'pending', 'total' => 500.000]);
        Order::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'delivered', 'total' => 1000.000]);
        Order::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'delivered', 'total' => 1500.000]);
        Order::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'cancelled', 'total' => 300.000]);

        $stats = $this->orderService->getVendorOrderStats($this->vendor);

        $this->assertEquals(4, $stats['total_orders']);
        $this->assertEquals(1, $stats['pending_orders']);
        $this->assertEquals(2, $stats['delivered_orders']);
        $this->assertEquals(1, $stats['cancelled_orders']);
        $this->assertEquals(2500.000, $stats['total_spent']);
    }

    /** @test */
    public function it_can_get_admin_order_statistics()
    {
        Order::factory()->create(['status' => 'pending', 'total' => 100.000]);
        Order::factory()->create(['status' => 'confirmed', 'total' => 200.000]);
        Order::factory()->create(['status' => 'delivered', 'total' => 300.000]);
        Order::factory()->create(['status' => 'delivered', 'total' => 400.000]);
        Order::factory()->create(['status' => 'cancelled', 'total' => 150.000]);

        $stats = $this->orderService->getAdminOrderStats();

        $this->assertEquals(5, $stats['total_orders']);
        $this->assertEquals(1, $stats['pending_orders']);
        $this->assertEquals(1, $stats['confirmed_orders']);
        $this->assertEquals(2, $stats['delivered_orders']);
        $this->assertEquals(1, $stats['cancelled_orders']);
        $this->assertEquals(700.000, $stats['total_revenue']);
    }

    /** @test */
    public function it_generates_unique_order_numbers()
    {
        $cartItems = [['product_id' => $this->product->id, 'quantity' => 1]];

        $order1 = $this->orderService->createOrder($this->vendor, $cartItems);
        $order2 = $this->orderService->createOrder($this->vendor, $cartItems);

        $this->assertNotEquals($order1->order_number, $order2->order_number);
        $this->assertStringStartsWith('ORD-', $order1->order_number);
        $this->assertStringStartsWith('ORD-', $order2->order_number);
    }

    /** @test */
    public function it_validates_cart_items_before_order_creation()
    {
        $inactiveProduct = Product::factory()->create(['is_active' => false]);

        $validation = $this->orderService->validateCart($this->vendor, [
            ['product_id' => $inactiveProduct->id, 'quantity' => 1],
            ['product_id' => 99999, 'quantity' => 1], // Non-existent
        ]);

        $this->assertFalse($validation['valid']);
        $this->assertCount(2, $validation['errors']);
    }

    /** @test */
    public function it_validates_minimum_order_quantity()
    {
        $this->product->update(['minimum_order_quantity' => 10]);

        $validation = $this->orderService->validateCart($this->vendor, [
            ['product_id' => $this->product->id, 'quantity' => 5],
        ]);

        $this->assertFalse($validation['valid']);
        $this->assertStringContainsString('Minimum quantity', $validation['errors'][0]);
    }

    /** @test */
    public function it_validates_order_multiple()
    {
        $this->product->update(['order_multiple' => 5]);

        $validation = $this->orderService->validateCart($this->vendor, [
            ['product_id' => $this->product->id, 'quantity' => 7],
        ]);

        $this->assertFalse($validation['valid']);
        $this->assertStringContainsString('must be multiple of', $validation['errors'][0]);
    }

    /** @test */
    public function it_validates_stock_availability()
    {
        $this->product->update(['stock_quantity' => 10, 'allow_backorder' => false]);

        $validation = $this->orderService->validateCart($this->vendor, [
            ['product_id' => $this->product->id, 'quantity' => 20],
        ]);

        $this->assertFalse($validation['valid']);
        $this->assertStringContainsString('Insufficient stock', $validation['errors'][0]);
    }
}

<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Models\StockMovement;
use App\Services\Inventory\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockService $stockService;
    protected Product $product;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService = new StockService();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin);

        $this->product = Product::factory()->create([
            'sku' => 'TEST-001',
            'stock_quantity' => 100,
            'base_price' => 50.000,
            'allow_backorder' => false,
            'low_stock_threshold' => 10,
        ]);
    }

    /** @test */
    public function it_can_add_stock_to_product()
    {
        $movement = $this->stockService->addStock($this->product, 50, 'Restock from supplier');

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'in',
            'quantity' => 50,
            'quantity_before' => 100,
            'quantity_after' => 150,
        ]);

        $this->product->refresh();
        $this->assertEquals(150, $this->product->stock_quantity);
    }

    /** @test */
    public function it_throws_exception_when_adding_negative_quantity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be positive');

        $this->stockService->addStock($this->product, -10);
    }

    /** @test */
    public function it_can_remove_stock_from_product()
    {
        $movement = $this->stockService->removeStock($this->product, 30, 'Damaged goods');

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'out',
            'quantity' => 30,
            'quantity_before' => 100,
            'quantity_after' => 70,
        ]);

        $this->product->refresh();
        $this->assertEquals(70, $this->product->stock_quantity);
    }

    /** @test */
    public function it_throws_exception_when_removing_more_stock_than_available()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->stockService->removeStock($this->product, 150);
    }

    /** @test */
    public function it_allows_removing_stock_when_backorder_is_enabled()
    {
        $this->product->update(['allow_backorder' => true]);

        $movement = $this->stockService->removeStock($this->product, 150);

        $this->product->refresh();
        $this->assertEquals(0, $this->product->stock_quantity); // Capped at 0
    }

    /** @test */
    public function it_can_adjust_stock_to_specific_quantity()
    {
        $movement = $this->stockService->adjustStock($this->product, 200, 'Physical inventory count');

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'adjustment',
            'quantity' => 100, // abs(200 - 100)
            'quantity_before' => 100,
            'quantity_after' => 200,
        ]);

        $this->product->refresh();
        $this->assertEquals(200, $this->product->stock_quantity);
    }

    /** @test */
    public function it_throws_exception_when_adjusting_to_same_quantity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('New quantity is same as current quantity');

        $this->stockService->adjustStock($this->product, 100);
    }

    /** @test */
    public function it_throws_exception_when_adjusting_to_negative_quantity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock quantity cannot be negative');

        $this->stockService->adjustStock($this->product, -10);
    }

    /** @test */
    public function it_can_reserve_stock_for_order()
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-001',
            'status' => 'pending',
        ]);

        $movement = $this->stockService->reserveStock($this->product, 20, $order);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'reserved',
            'quantity' => 20,
            'quantity_before' => 100,
            'quantity_after' => 100, // Stock doesn't change on reservation
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);

        $this->product->refresh();
        $this->assertEquals(100, $this->product->stock_quantity); // Unchanged
    }

    /** @test */
    public function it_throws_exception_when_reserving_more_than_available()
    {
        $order = Order::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->stockService->reserveStock($this->product, 150, $order);
    }

    /** @test */
    public function it_can_release_reserved_stock()
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-002',
            'status' => 'cancelled',
        ]);

        $movement = $this->stockService->releaseStock($this->product, 20, $order);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'released',
            'quantity' => 20,
            'quantity_before' => 100,
            'quantity_after' => 100, // Stock doesn't change
        ]);
    }

    /** @test */
    public function it_can_confirm_stock_deduction_when_order_ships()
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-003',
            'status' => 'shipped',
        ]);

        $movement = $this->stockService->confirmStockDeduction($this->product, 25, $order);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'out',
            'quantity' => 25,
            'quantity_before' => 100,
            'quantity_after' => 75,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);

        $this->product->refresh();
        $this->assertEquals(75, $this->product->stock_quantity);
    }

    /** @test */
    public function it_can_return_stock_from_rma()
    {
        $rma = (object) ['id' => 1, 'rma_number' => 'RMA-001'];

        $movement = $this->stockService->returnStock($this->product, 10, $rma);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'in',
            'quantity' => 10,
        ]);

        $this->product->refresh();
        $this->assertEquals(110, $this->product->stock_quantity);
    }

    /** @test */
    public function it_can_get_low_stock_products()
    {
        Product::factory()->create(['stock_quantity' => 5, 'low_stock_threshold' => 10, 'is_active' => true]);
        Product::factory()->create(['stock_quantity' => 50, 'low_stock_threshold' => 10, 'is_active' => true]);
        Product::factory()->create(['stock_quantity' => 3, 'low_stock_threshold' => 10, 'is_active' => true]);

        $lowStockProducts = $this->stockService->getLowStockProducts();

        $this->assertEquals(2, $lowStockProducts->count());
    }

    /** @test */
    public function it_can_get_out_of_stock_products()
    {
        Product::factory()->create(['stock_quantity' => 0, 'allow_backorder' => false, 'is_active' => true]);
        Product::factory()->create(['stock_quantity' => 0, 'allow_backorder' => false, 'is_active' => true]);
        Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);
        Product::factory()->create(['stock_quantity' => 0, 'allow_backorder' => true, 'is_active' => true]); // Not included

        $outOfStock = $this->stockService->getOutOfStockProducts();

        $this->assertEquals(2, $outOfStock->count());
    }

    /** @test */
    public function it_can_calculate_stock_value()
    {
        $value = $this->stockService->getStockValue($this->product);

        $this->assertEquals(5000.000, $value); // 100 * 50.000
    }

    /** @test */
    public function it_can_get_available_stock_considering_reservations()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        // Reserve 30 units
        $this->stockService->reserveStock($this->product, 30, $order);

        $availableStock = $this->stockService->getAvailableStock($this->product);

        $this->assertEquals(70, $availableStock); // 100 - 30 reserved
    }

    /** @test */
    public function it_checks_if_product_has_available_stock()
    {
        $this->assertTrue($this->stockService->hasAvailableStock($this->product, 50));
        $this->assertTrue($this->stockService->hasAvailableStock($this->product, 100));
        $this->assertFalse($this->stockService->hasAvailableStock($this->product, 150));
    }

    /** @test */
    public function it_allows_ordering_when_backorder_is_enabled()
    {
        $this->product->update(['allow_backorder' => true]);

        $this->assertTrue($this->stockService->hasAvailableStock($this->product, 200));
    }

    /** @test */
    public function it_can_get_inventory_statistics()
    {
        Product::factory()->create(['stock_quantity' => 50, 'base_price' => 10.000, 'is_active' => true]);
        Product::factory()->create(['stock_quantity' => 0, 'base_price' => 20.000, 'is_active' => true]);

        $stats = $this->stockService->getInventoryStats();

        $this->assertArrayHasKey('total_products', $stats);
        $this->assertArrayHasKey('in_stock_products', $stats);
        $this->assertArrayHasKey('low_stock_products', $stats);
        $this->assertArrayHasKey('out_of_stock_products', $stats);
        $this->assertArrayHasKey('total_stock_value', $stats);
        $this->assertArrayHasKey('in_stock_percentage', $stats);
    }

    /** @test */
    public function it_can_bulk_import_stock()
    {
        $product2 = Product::factory()->create(['sku' => 'TEST-002', 'stock_quantity' => 0]);

        $stockData = [
            ['sku' => 'TEST-001', 'quantity' => 50, 'notes' => 'Import batch 1'],
            ['sku' => 'TEST-002', 'quantity' => 100, 'notes' => 'Import batch 1'],
            ['sku' => 'INVALID', 'quantity' => 10, 'notes' => 'Should fail'],
        ];

        $result = $this->stockService->bulkImportStock($stockData);

        $this->assertEquals(2, $result['success']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);

        $this->product->refresh();
        $product2->refresh();

        $this->assertEquals(150, $this->product->stock_quantity);
        $this->assertEquals(100, $product2->stock_quantity);
    }

    /** @test */
    public function it_can_get_product_stock_history()
    {
        $this->stockService->addStock($this->product, 50);
        $this->stockService->removeStock($this->product, 20);
        $this->stockService->adjustStock($this->product, 200);

        $history = $this->stockService->getProductStockHistory($this->product)->get();

        $this->assertEquals(3, $history->count());
    }

    /** @test */
    public function it_can_filter_stock_history_by_type()
    {
        $this->stockService->addStock($this->product, 50);
        $this->stockService->removeStock($this->product, 20);
        $this->stockService->adjustStock($this->product, 200);

        $history = $this->stockService->getProductStockHistory($this->product, ['type' => 'in'])->get();

        $this->assertEquals(1, $history->count());
        $this->assertEquals('in', $history->first()->type);
    }
}

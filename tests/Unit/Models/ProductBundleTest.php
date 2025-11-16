<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Product\ProductBundle;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductBundleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function bundle_belongs_to_bundle_product()
    {
        $bundleProduct = Product::factory()->create(['name' => 'Starter Kit']);
        $bundleItem = ProductBundle::factory()->create(['bundle_product_id' => $bundleProduct->id]);

        $this->assertEquals($bundleProduct->id, $bundleItem->bundle_product_id);
        $this->assertEquals('Starter Kit', $bundleItem->bundleProduct->name);
    }

    /** @test */
    public function bundle_belongs_to_item_product()
    {
        $itemProduct = Product::factory()->create(['name' => 'Laptop']);
        $bundleItem = ProductBundle::factory()->create(['product_id' => $itemProduct->id]);

        $this->assertEquals($itemProduct->id, $bundleItem->product_id);
        $this->assertEquals('Laptop', $bundleItem->product->name);
    }

    /** @test */
    public function bundle_has_quantity()
    {
        $bundleItem = ProductBundle::factory()->create(['quantity' => 2]);

        $this->assertEquals(2, $bundleItem->quantity);
    }

    /** @test */
    public function bundle_has_discount_percentage()
    {
        $bundleItem = ProductBundle::factory()->create(['discount_percentage' => 15.5]);

        $this->assertEquals(15.5, $bundleItem->discount_percentage);
    }

    /** @test */
    public function unit_price_is_fetched_from_product()
    {
        $product = Product::factory()->create(['base_price' => 100.00]);
        $bundleItem = ProductBundle::factory()->create([
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertEquals(100.00, $bundleItem->unit_price);
    }

    /** @test */
    public function subtotal_calculated_from_quantity_and_price()
    {
        $product = Product::factory()->create(['base_price' => 50.00]);
        $bundleItem = ProductBundle::factory()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'discount_percentage' => 0,
        ]);

        $expected = 50.00 * 3;
        $this->assertEquals($expected, $bundleItem->subtotal);
    }

    /** @test */
    public function discount_amount_calculated_correctly()
    {
        $product = Product::factory()->create(['base_price' => 100.00]);
        $bundleItem = ProductBundle::factory()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'discount_percentage' => 20,
        ]);

        // Subtotal: 100 * 2 = 200
        // Discount: 200 * 0.20 = 40
        $this->assertEquals(40.00, $bundleItem->discount_amount);
    }

    /** @test */
    public function discounted_price_calculated_correctly()
    {
        $product = Product::factory()->create(['base_price' => 100.00]);
        $bundleItem = ProductBundle::factory()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'discount_percentage' => 25,
        ]);

        // Subtotal: 100 * 2 = 200
        // Discount: 200 * 0.25 = 50
        // Final: 200 - 50 = 150
        $this->assertEquals(150.00, $bundleItem->discounted_price);
    }

    /** @test */
    public function discounted_price_equals_subtotal_when_no_discount()
    {
        $product = Product::factory()->create(['base_price' => 100.00]);
        $bundleItem = ProductBundle::factory()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'discount_percentage' => 0,
        ]);

        $this->assertEquals(200.00, $bundleItem->discounted_price);
        $this->assertEquals($bundleItem->subtotal, $bundleItem->discounted_price);
    }

    /** @test */
    public function bundle_can_have_custom_price_override()
    {
        $product = Product::factory()->create(['base_price' => 100.00]);
        $bundleItem = ProductBundle::factory()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'custom_price' => 85.00,
        ]);

        $this->assertEquals(85.00, $bundleItem->custom_price);
    }

    /** @test */
    public function bundle_uses_custom_price_when_provided()
    {
        $product = Product::factory()->create(['base_price' => 100.00]);
        $bundleItem = ProductBundle::factory()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'custom_price' => 80.00,
            'discount_percentage' => 0,
        ]);

        // When custom price is set, use it instead of base price
        $expected = 80.00 * 2;
        $this->assertEquals($expected, $bundleItem->final_price);
    }

    /** @test */
    public function bundle_can_have_meta_data()
    {
        $bundleItem = ProductBundle::factory()->create([
            'meta' => ['notes' => 'Special bundle item', 'priority' => 'high'],
        ]);

        $this->assertIsArray($bundleItem->meta);
        $this->assertEquals('Special bundle item', $bundleItem->meta['notes']);
        $this->assertEquals('high', $bundleItem->meta['priority']);
    }

    /** @test */
    public function bundle_savings_calculated_correctly()
    {
        $product = Product::factory()->create(['base_price' => 100.00]);
        $bundleItem = ProductBundle::factory()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'discount_percentage' => 20,
        ]);

        // Original: 100 * 3 = 300
        // Discount: 300 * 0.20 = 60
        // Savings: 60
        $this->assertEquals(60.00, $bundleItem->savings);
    }

    /** @test */
    public function bundle_has_timestamps()
    {
        $bundleItem = ProductBundle::factory()->create();

        $this->assertNotNull($bundleItem->created_at);
        $this->assertNotNull($bundleItem->updated_at);
    }

    /** @test */
    public function bundle_order_can_be_set()
    {
        $item1 = ProductBundle::factory()->create(['order' => 0]);
        $item2 = ProductBundle::factory()->create(['order' => 1]);
        $item3 = ProductBundle::factory()->create(['order' => 2]);

        $orderedItems = ProductBundle::orderBy('order')->get();

        $this->assertEquals($item1->id, $orderedItems->first()->id);
        $this->assertEquals($item3->id, $orderedItems->last()->id);
    }

    /** @test */
    public function bundle_item_can_be_required_or_optional()
    {
        $required = ProductBundle::factory()->create(['is_required' => true]);
        $optional = ProductBundle::factory()->create(['is_required' => false]);

        $this->assertTrue($required->is_required);
        $this->assertFalse($optional->is_required);
    }

    /** @test */
    public function bundle_total_for_multiple_items_calculated_correctly()
    {
        $bundleProduct = Product::factory()->create();

        $product1 = Product::factory()->create(['base_price' => 100.00]);
        $product2 = Product::factory()->create(['base_price' => 50.00]);
        $product3 = Product::factory()->create(['base_price' => 75.00]);

        $item1 = ProductBundle::factory()->create([
            'bundle_product_id' => $bundleProduct->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'discount_percentage' => 10,
        ]);

        $item2 = ProductBundle::factory()->create([
            'bundle_product_id' => $bundleProduct->id,
            'product_id' => $product2->id,
            'quantity' => 3,
            'discount_percentage' => 15,
        ]);

        $item3 = ProductBundle::factory()->create([
            'bundle_product_id' => $bundleProduct->id,
            'product_id' => $product3->id,
            'quantity' => 1,
            'discount_percentage' => 5,
        ]);

        $bundleItems = ProductBundle::where('bundle_product_id', $bundleProduct->id)->get();
        $total = $bundleItems->sum(fn($item) => $item->discounted_price);

        // Item 1: (100 * 2) - (200 * 0.10) = 180
        // Item 2: (50 * 3) - (150 * 0.15) = 127.50
        // Item 3: (75 * 1) - (75 * 0.05) = 71.25
        // Total: 378.75
        $this->assertEquals(378.75, $total);
    }
}

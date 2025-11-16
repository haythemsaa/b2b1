<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Product\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function variant_belongs_to_product()
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->assertEquals($product->id, $variant->product_id);
        $this->assertEquals($product->name, $variant->product->name);
    }

    /** @test */
    public function variant_attributes_are_cast_to_array()
    {
        $variant = ProductVariant::factory()->create([
            'attributes' => ['size' => 'L', 'color' => '#FF0000'],
        ]);

        $this->assertIsArray($variant->attributes);
        $this->assertEquals('L', $variant->attributes['size']);
        $this->assertEquals('#FF0000', $variant->attributes['color']);
    }

    /** @test */
    public function display_name_returns_custom_name_if_set()
    {
        $variant = ProductVariant::factory()->create([
            'name' => 'Premium Edition',
            'attributes' => ['size' => 'L'],
        ]);

        $this->assertEquals('Premium Edition', $variant->display_name);
    }

    /** @test */
    public function display_name_returns_formatted_attributes_if_no_name()
    {
        $variant = ProductVariant::factory()->create([
            'name' => null,
            'attributes' => ['size' => 'L', 'color' => 'Red'],
        ]);

        $this->assertEquals('L / Red', $variant->display_name);
    }

    /** @test */
    public function discount_percentage_calculated_from_compare_price()
    {
        $variant = ProductVariant::factory()->create([
            'price' => 75.00,
            'compare_price' => 100.00,
        ]);

        $this->assertEquals(25.0, $variant->discount_percentage);
    }

    /** @test */
    public function discount_percentage_is_null_when_no_compare_price()
    {
        $variant = ProductVariant::factory()->create([
            'price' => 75.00,
            'compare_price' => null,
        ]);

        $this->assertNull($variant->discount_percentage);
    }

    /** @test */
    public function discount_percentage_is_null_when_compare_price_is_lower()
    {
        $variant = ProductVariant::factory()->create([
            'price' => 100.00,
            'compare_price' => 75.00,
        ]);

        $this->assertNull($variant->discount_percentage);
    }

    /** @test */
    public function variant_can_have_stock_quantity()
    {
        $variant = ProductVariant::factory()->create(['stock' => 150]);

        $this->assertEquals(150, $variant->stock);
    }

    /** @test */
    public function variant_can_have_moq()
    {
        $variant = ProductVariant::factory()->create(['moq' => 10]);

        $this->assertEquals(10, $variant->moq);
    }

    /** @test */
    public function variant_sku_must_be_unique()
    {
        ProductVariant::factory()->create(['sku' => 'UNIQUE-SKU-001']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ProductVariant::factory()->create(['sku' => 'UNIQUE-SKU-001']);
    }

    /** @test */
    public function variant_can_be_active_or_inactive()
    {
        $active = ProductVariant::factory()->create(['is_active' => true]);
        $inactive = ProductVariant::factory()->create(['is_active' => false]);

        $this->assertTrue($active->is_active);
        $this->assertFalse($inactive->is_active);
    }

    /** @test */
    public function variant_has_timestamps()
    {
        $variant = ProductVariant::factory()->create();

        $this->assertNotNull($variant->created_at);
        $this->assertNotNull($variant->updated_at);
    }

    /** @test */
    public function meta_is_cast_to_array()
    {
        $variant = ProductVariant::factory()->create([
            'meta' => ['barcode' => '123456789', 'weight' => '500g'],
        ]);

        $this->assertIsArray($variant->meta);
        $this->assertEquals('123456789', $variant->meta['barcode']);
        $this->assertEquals('500g', $variant->meta['weight']);
    }

    /** @test */
    public function variant_can_have_images()
    {
        $variant = ProductVariant::factory()->create([
            'images' => ['image1.jpg', 'image2.jpg', 'image3.jpg'],
        ]);

        $this->assertIsArray($variant->images);
        $this->assertCount(3, $variant->images);
        $this->assertEquals('image1.jpg', $variant->images[0]);
    }

    /** @test */
    public function is_in_stock_returns_true_when_stock_available()
    {
        $inStock = ProductVariant::factory()->create(['stock' => 10]);
        $outOfStock = ProductVariant::factory()->create(['stock' => 0]);

        $this->assertTrue($inStock->is_in_stock);
        $this->assertFalse($outOfStock->is_in_stock);
    }

    /** @test */
    public function is_low_stock_returns_true_when_stock_below_threshold()
    {
        $lowStock = ProductVariant::factory()->create(['stock' => 5, 'low_stock_threshold' => 10]);
        $normalStock = ProductVariant::factory()->create(['stock' => 20, 'low_stock_threshold' => 10]);

        $this->assertTrue($lowStock->is_low_stock);
        $this->assertFalse($normalStock->is_low_stock);
    }

    /** @test */
    public function final_price_equals_price_when_no_discount()
    {
        $variant = ProductVariant::factory()->create([
            'price' => 100.00,
            'compare_price' => null,
        ]);

        $this->assertEquals(100.00, $variant->final_price);
    }

    /** @test */
    public function savings_amount_calculated_correctly()
    {
        $variant = ProductVariant::factory()->create([
            'price' => 75.00,
            'compare_price' => 100.00,
        ]);

        $this->assertEquals(25.00, $variant->savings_amount);
    }
}

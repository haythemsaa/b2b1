<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductAttribute;
use App\Models\Product\ProductVariant;
use App\Models\Product\ProductBundle;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdvancedProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $vendor;
    protected ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->vendor = User::factory()->create(['role' => 'vendor', 'status' => 'active']);
        $this->category = ProductCategory::factory()->create();
    }

    /** @test */
    public function admin_can_create_simple_product()
    {
        $productData = [
            'type' => 'simple',
            'name' => 'Basic T-Shirt',
            'sku' => 'TSHIRT-001',
            'base_price' => 25.00,
            'stock_quantity' => 100,
            'moq' => 5,
            'category_id' => $this->category->id,
            'description' => 'A basic t-shirt',
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/products/advanced', $productData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Basic T-Shirt']);

        $this->assertDatabaseHas('products', [
            'sku' => 'TSHIRT-001',
            'base_price' => 25.00,
        ]);

        $product = Product::where('sku', 'TSHIRT-001')->first();
        $this->assertEquals('simple', $product->meta_data['type'] ?? null);
    }

    /** @test */
    public function admin_can_create_variable_product_with_variants()
    {
        $sizeAttr = ProductAttribute::factory()->create([
            'slug' => 'size',
            'type' => 'select',
            'is_variant' => true,
        ]);
        $colorAttr = ProductAttribute::factory()->create([
            'slug' => 'color',
            'type' => 'color',
            'is_variant' => true,
        ]);

        $productData = [
            'type' => 'variable',
            'name' => 'Variable T-Shirt',
            'sku' => 'TSHIRT-VAR-001',
            'base_price' => 25.00,
            'category_id' => $this->category->id,
            'variants' => [
                [
                    'sku' => 'TSHIRT-VAR-S-RED',
                    'attributes' => ['size' => 'S', 'color' => '#FF0000'],
                    'price' => 25.00,
                    'stock' => 50,
                    'moq' => 5,
                ],
                [
                    'sku' => 'TSHIRT-VAR-M-BLUE',
                    'attributes' => ['size' => 'M', 'color' => '#0000FF'],
                    'price' => 27.00,
                    'stock' => 75,
                    'moq' => 5,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/products/advanced', $productData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Variable T-Shirt']);

        $product = Product::where('sku', 'TSHIRT-VAR-001')->first();
        $this->assertEquals('variable', $product->meta_data['type']);
        $this->assertCount(2, $product->variants);

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'TSHIRT-VAR-S-RED',
            'price' => 25.00,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'TSHIRT-VAR-M-BLUE',
            'price' => 27.00,
        ]);
    }

    /** @test */
    public function admin_can_create_bundle_product()
    {
        $item1 = Product::factory()->create(['base_price' => 10.00]);
        $item2 = Product::factory()->create(['base_price' => 15.00]);

        $productData = [
            'type' => 'bundle',
            'name' => 'Starter Kit',
            'sku' => 'BUNDLE-001',
            'base_price' => 20.00, // Discounted from 25
            'category_id' => $this->category->id,
            'bundle_items' => [
                [
                    'product_id' => $item1->id,
                    'quantity' => 1,
                    'discount_percentage' => 10,
                ],
                [
                    'product_id' => $item2->id,
                    'quantity' => 1,
                    'discount_percentage' => 15,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/products/advanced', $productData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Starter Kit']);

        $product = Product::where('sku', 'BUNDLE-001')->first();
        $this->assertEquals('bundle', $product->meta_data['type']);
        $this->assertCount(2, $product->bundleItems);

        $this->assertDatabaseHas('product_bundles', [
            'bundle_product_id' => $product->id,
            'product_id' => $item1->id,
            'quantity' => 1,
            'discount_percentage' => 10,
        ]);
    }

    /** @test */
    public function admin_can_create_configurable_product()
    {
        $productData = [
            'type' => 'configurable',
            'name' => 'Custom Laptop',
            'sku' => 'LAPTOP-CONFIG-001',
            'base_price' => 1000.00,
            'category_id' => $this->category->id,
            'custom_options' => [
                [
                    'name' => 'RAM',
                    'type' => 'select',
                    'options' => ['8GB', '16GB', '32GB'],
                    'price_modifier' => [0, 100, 300],
                    'required' => true,
                ],
                [
                    'name' => 'Storage',
                    'type' => 'select',
                    'options' => ['256GB SSD', '512GB SSD', '1TB SSD'],
                    'price_modifier' => [0, 50, 150],
                    'required' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/products/advanced', $productData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Custom Laptop']);

        $product = Product::where('sku', 'LAPTOP-CONFIG-001')->first();
        $this->assertEquals('configurable', $product->meta_data['type']);
        $this->assertArrayHasKey('custom_options', $product->meta_data);
        $this->assertCount(2, $product->meta_data['custom_options']);
    }

    /** @test */
    public function admin_can_update_variable_product_variants()
    {
        $product = Product::factory()->create([
            'sku' => 'TSHIRT-VAR',
            'meta_data' => ['type' => 'variable'],
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'TSHIRT-VAR-S',
            'price' => 25.00,
            'stock' => 50,
        ]);

        $updateData = [
            'type' => 'variable',
            'variants' => [
                [
                    'id' => $variant->id,
                    'price' => 30.00,
                    'stock' => 100,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/products/{$product->id}/advanced", $updateData);

        $response->assertStatus(200);

        $variant->refresh();
        $this->assertEquals(30.00, $variant->price);
        $this->assertEquals(100, $variant->stock);
    }

    /** @test */
    public function admin_can_add_new_variants_to_variable_product()
    {
        $product = Product::factory()->create([
            'sku' => 'TSHIRT-VAR',
            'meta_data' => ['type' => 'variable'],
        ]);

        $updateData = [
            'type' => 'variable',
            'variants' => [
                [
                    'sku' => 'TSHIRT-VAR-NEW',
                    'attributes' => ['size' => 'XL'],
                    'price' => 30.00,
                    'stock' => 25,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/products/{$product->id}/advanced", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'TSHIRT-VAR-NEW',
            'price' => 30.00,
        ]);
    }

    /** @test */
    public function admin_can_duplicate_product()
    {
        $original = Product::factory()->create([
            'name' => 'Original Product',
            'sku' => 'ORIG-001',
            'meta_data' => ['type' => 'simple'],
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/products/{$original->id}/duplicate", [
                'new_sku' => 'DUPL-001',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Original Product (Copy)']);

        $this->assertDatabaseHas('products', [
            'sku' => 'DUPL-001',
            'name' => 'Original Product (Copy)',
        ]);
    }

    /** @test */
    public function admin_can_bulk_update_stock()
    {
        $product1 = Product::factory()->create(['sku' => 'PROD-001', 'stock_quantity' => 50]);
        $product2 = Product::factory()->create(['sku' => 'PROD-002', 'stock_quantity' => 75]);

        $updateData = [
            'updates' => [
                ['id' => $product1->id, 'stock_quantity' => 100],
                ['id' => $product2->id, 'stock_quantity' => 150],
            ],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/products/bulk-update-stock', $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['updated' => 2]);

        $product1->refresh();
        $product2->refresh();

        $this->assertEquals(100, $product1->stock_quantity);
        $this->assertEquals(150, $product2->stock_quantity);
    }

    /** @test */
    public function variant_sku_must_be_unique()
    {
        $product = Product::factory()->create([
            'meta_data' => ['type' => 'variable'],
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'TSHIRT-S-RED',
        ]);

        $productData = [
            'type' => 'variable',
            'name' => 'Another T-Shirt',
            'sku' => 'TSHIRT-002',
            'base_price' => 25.00,
            'category_id' => $this->category->id,
            'variants' => [
                [
                    'sku' => 'TSHIRT-S-RED', // Duplicate SKU
                    'attributes' => ['size' => 'S'],
                    'price' => 25.00,
                    'stock' => 50,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/products/advanced', $productData);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Variant SKU TSHIRT-S-RED already exists']);
    }

    /** @test */
    public function bundle_items_must_exist()
    {
        $productData = [
            'type' => 'bundle',
            'name' => 'Invalid Bundle',
            'sku' => 'BUNDLE-INVALID',
            'base_price' => 50.00,
            'category_id' => $this->category->id,
            'bundle_items' => [
                [
                    'product_id' => 99999, // Non-existent product
                    'quantity' => 1,
                    'discount_percentage' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/products/advanced', $productData);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Bundle item product not found']);
    }

    /** @test */
    public function vendor_can_create_products_for_their_account()
    {
        $productData = [
            'type' => 'simple',
            'name' => 'Vendor Product',
            'sku' => 'VENDOR-001',
            'base_price' => 50.00,
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/admin/products/advanced', $productData);

        $response->assertStatus(201);

        $product = Product::where('sku', 'VENDOR-001')->first();
        $this->assertEquals($this->vendor->id, $product->vendor_id);
    }

    /** @test */
    public function product_with_compare_price_shows_discount()
    {
        $productData = [
            'type' => 'simple',
            'name' => 'Discounted Product',
            'sku' => 'DISC-001',
            'base_price' => 75.00,
            'compare_price' => 100.00,
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/products/advanced', $productData);

        $response->assertStatus(201);

        $product = Product::where('sku', 'DISC-001')->first();
        $this->assertEquals(100.00, $product->meta_data['compare_price']);

        // Calculate discount percentage
        $discount = (($product->meta_data['compare_price'] - $product->base_price) / $product->meta_data['compare_price']) * 100;
        $this->assertEquals(25, round($discount));
    }

    /** @test */
    public function unauthenticated_user_cannot_create_products()
    {
        $productData = [
            'type' => 'simple',
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'base_price' => 25.00,
        ];

        $response = $this->postJson('/api/admin/products/advanced', $productData);

        $response->assertStatus(401);
    }
}

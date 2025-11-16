<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\VendorGroup;
use App\Models\ProductPricing;
use App\Models\ProductVendorVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $vendor;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->vendor = User::factory()->create(['role' => 'vendor', 'status' => 'active']);
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    /** @test */
    public function admin_can_list_all_products()
    {
        Product::factory()->count(5)->create(['is_active' => true]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'sku', 'name', 'base_price', 'stock_quantity'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    /** @test */
    public function vendor_cannot_access_admin_products_endpoint()
    {
        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/admin/products');

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_products()
    {
        $response = $this->getJson('/api/admin/products');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_can_create_product()
    {
        $productData = [
            'sku' => 'NEW-PROD-001',
            'name_fr' => 'Nouveau Produit',
            'name_ar' => 'منتج جديد',
            'description_fr' => 'Description en français',
            'description_ar' => 'وصف بالعربية',
            'category_id' => $this->category->id,
            'base_price' => 150.000,
            'stock_quantity' => 100,
            'minimum_order_quantity' => 1,
            'order_multiple' => 1,
            'low_stock_threshold' => 10,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'sku' => 'NEW-PROD-001',
                'name_fr' => 'Nouveau Produit',
            ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'NEW-PROD-001',
            'base_price' => 150.000,
        ]);
    }

    /** @test */
    public function product_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku', 'name_fr', 'category_id', 'base_price']);
    }

    /** @test */
    public function product_sku_must_be_unique()
    {
        Product::factory()->create(['sku' => 'EXISTING-001']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/products', [
                'sku' => 'EXISTING-001',
                'name_fr' => 'Test Product',
                'category_id' => $this->category->id,
                'base_price' => 100.000,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    /** @test */
    public function admin_can_update_product()
    {
        $product = Product::factory()->create([
            'sku' => 'PROD-001',
            'base_price' => 100.000,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/products/{$product->id}", [
                'base_price' => 120.000,
                'name_fr' => 'Updated Name',
            ]);

        $response->assertStatus(200);

        $product->refresh();
        $this->assertEquals(120.000, $product->base_price);
        $this->assertEquals('Updated Name', $product->name_fr);
    }

    /** @test */
    public function admin_can_delete_product()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/products/{$product->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    /** @test */
    public function admin_can_view_specific_product()
    {
        $product = Product::factory()->create(['sku' => 'VIEW-001']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson(['sku' => 'VIEW-001']);
    }

    /** @test */
    public function admin_can_adjust_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/products/{$product->id}/adjust-stock", [
                'new_quantity' => 150,
                'notes' => 'Physical inventory count',
            ]);

        $response->assertStatus(200);

        $product->refresh();
        $this->assertEquals(150, $product->stock_quantity);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity_after' => 150,
        ]);
    }

    /** @test */
    public function stock_adjustment_validates_quantity()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/products/{$product->id}/adjust-stock", [
                'new_quantity' => -10, // Negative not allowed
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function admin_can_get_stock_history()
    {
        $product = Product::factory()->create();

        // Create some stock movements
        $stockService = app(\App\Services\Inventory\StockService::class);
        $stockService->addStock($product, 50, 'Initial stock');
        $stockService->removeStock($product, 10, 'Sale');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/products/{$product->id}/stock-history");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['type', 'quantity', 'quantity_before', 'quantity_after', 'created_at'],
                ],
            ]);
    }

    /** @test */
    public function admin_can_set_vendor_specific_pricing()
    {
        $product = Product::factory()->create(['base_price' => 100.000]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/products/{$product->id}/vendor-pricing", [
                'vendor_id' => $this->vendor->id,
                'price' => 85.000,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('product_pricing', [
            'product_id' => $product->id,
            'vendor_id' => $this->vendor->id,
            'price' => 85.000,
        ]);
    }

    /** @test */
    public function admin_can_set_group_pricing()
    {
        $vendorGroup = VendorGroup::factory()->create();
        $product = Product::factory()->create(['base_price' => 100.000]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/products/{$product->id}/group-pricing", [
                'vendor_group_id' => $vendorGroup->id,
                'price' => 90.000,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('product_pricing', [
            'product_id' => $product->id,
            'vendor_group_id' => $vendorGroup->id,
            'price' => 90.000,
        ]);
    }

    /** @test */
    public function admin_can_set_vendor_visibility()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/products/{$product->id}/vendor-visibility", [
                'vendor_id' => $this->vendor->id,
                'is_visible' => false,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('product_vendor_visibility', [
            'product_id' => $product->id,
            'vendor_id' => $this->vendor->id,
            'is_visible' => false,
        ]);
    }

    /** @test */
    public function admin_can_get_inventory_statistics()
    {
        Product::factory()->count(10)->create(['is_active' => true, 'stock_quantity' => 50]);
        Product::factory()->count(3)->create(['is_active' => true, 'stock_quantity' => 0]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/products/inventory-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_products',
                'in_stock_products',
                'low_stock_products',
                'out_of_stock_products',
                'total_stock_value',
            ]);
    }

    /** @test */
    public function admin_can_get_low_stock_products()
    {
        Product::factory()->create(['stock_quantity' => 5, 'low_stock_threshold' => 10, 'is_active' => true]);
        Product::factory()->create(['stock_quantity' => 3, 'low_stock_threshold' => 10, 'is_active' => true]);
        Product::factory()->create(['stock_quantity' => 50, 'low_stock_threshold' => 10, 'is_active' => true]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/products/low-stock');

        $response->assertStatus(200);

        $products = $response->json();
        $this->assertEquals(2, count($products));

        foreach ($products as $product) {
            $this->assertLessThan($product['low_stock_threshold'], $product['stock_quantity']);
        }
    }

    /** @test */
    public function admin_can_filter_products_by_category()
    {
        $category2 = Category::factory()->create();

        Product::factory()->count(3)->create(['category_id' => $this->category->id]);
        Product::factory()->count(2)->create(['category_id' => $category2->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/products?category_id={$this->category->id}");

        $response->assertStatus(200);

        $products = $response->json('data');
        foreach ($products as $product) {
            $this->assertEquals($this->category->id, $product['category_id']);
        }
    }

    /** @test */
    public function admin_can_search_products()
    {
        Product::factory()->create(['name_fr' => 'MacBook Pro', 'is_active' => true]);
        Product::factory()->create(['name_fr' => 'Dell Laptop', 'is_active' => true]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/products?search=MacBook');

        $response->assertStatus(200);

        $products = $response->json('data');
        $this->assertTrue(count($products) > 0);
        $this->assertStringContainsString('MacBook', $products[0]['name']);
    }

    /** @test */
    public function admin_can_filter_active_inactive_products()
    {
        Product::factory()->count(5)->create(['is_active' => true]);
        Product::factory()->count(3)->create(['is_active' => false]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/products?is_active=1');

        $response->assertStatus(200);

        $products = $response->json('data');
        foreach ($products as $product) {
            $this->assertTrue($product['is_active']);
        }
    }

    /** @test */
    public function admin_can_get_categories()
    {
        Category::factory()->count(5)->create(['is_active' => true]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/products/categories');

        $response->assertStatus(200);

        $this->assertGreaterThanOrEqual(5, count($response->json()));
    }

    /** @test */
    public function admin_can_paginate_products()
    {
        Product::factory()->count(50)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/products?per_page=20');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);

        $this->assertEquals(20, count($response->json('data')));
    }
}

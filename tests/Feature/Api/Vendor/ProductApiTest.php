<?php

namespace Tests\Feature\Api\Vendor;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\VendorProfile;
use App\Models\VendorGroup;
use App\Models\ProductVendorVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendor;
    protected VendorGroup $vendorGroup;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendorGroup = VendorGroup::factory()->create([
            'name' => 'Standard',
            'discount_percentage' => 10,
        ]);

        $this->vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);

        VendorProfile::factory()->create([
            'user_id' => $this->vendor->id,
            'vendor_group_id' => $this->vendorGroup->id,
        ]);

        $this->category = Category::factory()->create(['is_active' => true]);
    }

    /** @test */
    public function vendor_can_list_visible_products()
    {
        Product::factory()->count(5)->create(['is_active' => true]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'sku', 'name', 'base_price', 'stock_quantity'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    /** @test */
    public function unauthenticated_user_cannot_access_vendor_products()
    {
        $response = $this->getJson('/api/vendor/products');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_cannot_access_vendor_products_endpoint()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/vendor/products');

        $response->assertStatus(403);
    }

    /** @test */
    public function vendor_can_view_specific_product()
    {
        $product = Product::factory()->create([
            'sku' => 'TEST-001',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson("/api/vendor/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $product->id,
                'sku' => 'TEST-001',
            ]);
    }

    /** @test */
    public function vendor_cannot_view_hidden_product()
    {
        $product = Product::factory()->create(['is_active' => true]);

        ProductVendorVisibility::create([
            'product_id' => $product->id,
            'vendor_id' => $this->vendor->id,
            'is_visible' => false,
        ]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson("/api/vendor/products/{$product->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function vendor_cannot_view_inactive_product()
    {
        $product = Product::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson("/api/vendor/products/{$product->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function vendor_can_search_products()
    {
        Product::factory()->create(['name_fr' => 'iPhone 15', 'is_active' => true]);
        Product::factory()->create(['name_fr' => 'Samsung Galaxy', 'is_active' => true]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/products/search?q=iPhone');

        $response->assertStatus(200);

        $products = $response->json('data');
        $this->assertTrue(count($products) > 0);
        $this->assertStringContainsString('iPhone', $products[0]['name']);
    }

    /** @test */
    public function vendor_can_filter_products_by_category()
    {
        $category2 = Category::factory()->create(['is_active' => true]);

        Product::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        Product::factory()->count(2)->create([
            'category_id' => $category2->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson("/api/vendor/products?category_id={$this->category->id}");

        $response->assertStatus(200);

        $products = $response->json('data');
        foreach ($products as $product) {
            $this->assertEquals($this->category->id, $product['category_id']);
        }
    }

    /** @test */
    public function vendor_can_filter_in_stock_products()
    {
        Product::factory()->create(['stock_quantity' => 0, 'is_active' => true]);
        Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/products?in_stock=1');

        $response->assertStatus(200);

        $products = $response->json('data');
        foreach ($products as $product) {
            $this->assertGreaterThan(0, $product['stock_quantity']);
        }
    }

    /** @test */
    public function vendor_can_get_categories()
    {
        Category::factory()->count(3)->create(['is_active' => true]);

        // Create products for each category
        Category::all()->each(function ($category) {
            Product::factory()->count(2)->create([
                'category_id' => $category->id,
                'is_active' => true,
            ]);
        });

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/products/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'products_count'],
            ]);

        $this->assertGreaterThanOrEqual(3, count($response->json()));
    }

    /** @test */
    public function vendor_can_calculate_price_for_product()
    {
        $product = Product::factory()->create([
            'base_price' => 100.000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson("/api/vendor/products/{$product->id}/calculate-price", [
                'quantity' => 10,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'base_price',
                'final_price',
                'unit_price',
                'total_price',
                'quantity',
            ]);
    }

    /** @test */
    public function price_calculation_requires_quantity()
    {
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson("/api/vendor/products/{$product->id}/calculate-price", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    /** @test */
    public function vendor_can_sort_products_by_price()
    {
        Product::factory()->create(['base_price' => 50.000, 'is_active' => true]);
        Product::factory()->create(['base_price' => 150.000, 'is_active' => true]);
        Product::factory()->create(['base_price' => 100.000, 'is_active' => true]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/products?sort_by=price&sort_order=asc');

        $response->assertStatus(200);

        $products = $response->json('data');
        $prices = array_column($products, 'base_price');

        for ($i = 0; $i < count($prices) - 1; $i++) {
            $this->assertLessThanOrEqual($prices[$i + 1], $prices[$i]);
        }
    }

    /** @test */
    public function vendor_can_paginate_products()
    {
        Product::factory()->count(30)->create(['is_active' => true]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/products?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
                'last_page',
            ]);

        $this->assertEquals(10, count($response->json('data')));
    }

    /** @test */
    public function product_list_excludes_explicitly_hidden_products()
    {
        $product = Product::factory()->create(['is_active' => true]);

        ProductVendorVisibility::create([
            'product_id' => $product->id,
            'vendor_id' => $this->vendor->id,
            'is_visible' => false,
        ]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/products');

        $response->assertStatus(200);

        $productIds = array_column($response->json('data'), 'id');
        $this->assertNotContains($product->id, $productIds);
    }

    /** @test */
    public function vendor_sees_group_specific_visible_products()
    {
        $product = Product::factory()->create(['is_active' => true]);

        ProductVendorVisibility::create([
            'product_id' => $product->id,
            'vendor_group_id' => $this->vendorGroup->id,
            'vendor_id' => null,
            'is_visible' => true,
        ]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/products');

        $response->assertStatus(200);

        $productIds = array_column($response->json('data'), 'id');
        $this->assertContains($product->id, $productIds);
    }

    /** @test */
    public function inactive_vendor_cannot_access_products()
    {
        $this->vendor->update(['status' => 'inactive']);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/products');

        $response->assertStatus(403);
    }
}

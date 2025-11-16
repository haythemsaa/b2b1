<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorGroup;
use App\Models\Category;
use App\Models\ProductVendorVisibility;
use App\Services\Catalog\CatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CatalogService $catalogService;
    protected User $vendor;
    protected User $vendor2;
    protected VendorGroup $vipGroup;
    protected VendorGroup $standardGroup;
    protected Product $product;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalogService = new CatalogService();

        $this->vipGroup = VendorGroup::factory()->create(['name' => 'VIP']);
        $this->standardGroup = VendorGroup::factory()->create(['name' => 'Standard']);

        $this->vendor = User::factory()->create(['role' => 'vendor', 'status' => 'active']);
        VendorProfile::factory()->create([
            'user_id' => $this->vendor->id,
            'vendor_group_id' => $this->vipGroup->id,
        ]);

        $this->vendor2 = User::factory()->create(['role' => 'vendor', 'status' => 'active']);
        VendorProfile::factory()->create([
            'user_id' => $this->vendor2->id,
            'vendor_group_id' => $this->standardGroup->id,
        ]);

        $this->category = Category::factory()->create(['is_active' => true]);

        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'is_active' => true,
            'base_price' => 100.000,
            'stock_quantity' => 50,
        ]);
    }

    /** @test */
    public function it_can_get_visible_products_for_vendor()
    {
        Product::factory()->count(5)->create(['is_active' => true]);

        $products = $this->catalogService->getVisibleProductsForVendor($this->vendor)->get();

        $this->assertGreaterThanOrEqual(5, $products->count());
    }

    /** @test */
    public function it_throws_exception_when_non_vendor_requests_catalog()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User must be a vendor');

        $this->catalogService->getVisibleProductsForVendor($admin);
    }

    /** @test */
    public function product_is_visible_by_default_when_no_visibility_rules()
    {
        $isVisible = $this->catalogService->isProductVisible($this->product, $this->vendor);

        $this->assertTrue($isVisible);
    }

    /** @test */
    public function inactive_product_is_not_visible()
    {
        $this->product->update(['is_active' => false]);

        $isVisible = $this->catalogService->isProductVisible($this->product, $this->vendor);

        $this->assertFalse($isVisible);
    }

    /** @test */
    public function it_respects_vendor_specific_visibility_hide()
    {
        $this->catalogService->setProductVisibilityForVendor($this->product, $this->vendor, false);

        $isVisible = $this->catalogService->isProductVisible($this->product, $this->vendor);

        $this->assertFalse($isVisible);
    }

    /** @test */
    public function it_respects_vendor_specific_visibility_show()
    {
        $this->catalogService->setProductVisibilityForVendor($this->product, $this->vendor, true);

        $isVisible = $this->catalogService->isProductVisible($this->product, $this->vendor);

        $this->assertTrue($isVisible);
    }

    /** @test */
    public function it_respects_group_level_visibility()
    {
        $this->catalogService->setProductVisibilityForGroup($this->product, $this->vipGroup->id, false);

        $isVisible = $this->catalogService->isProductVisible($this->product, $this->vendor);

        $this->assertFalse($isVisible);
    }

    /** @test */
    public function vendor_specific_visibility_overrides_group_visibility()
    {
        // Hide for VIP group
        $this->catalogService->setProductVisibilityForGroup($this->product, $this->vipGroup->id, false);

        // But explicitly show to this specific vendor
        $this->catalogService->setProductVisibilityForVendor($this->product, $this->vendor, true);

        $isVisible = $this->catalogService->isProductVisible($this->product, $this->vendor);

        $this->assertTrue($isVisible);
    }

    /** @test */
    public function it_can_set_product_visibility_for_vendor()
    {
        $visibility = $this->catalogService->setProductVisibilityForVendor($this->product, $this->vendor, false);

        $this->assertDatabaseHas('product_vendor_visibility', [
            'product_id' => $this->product->id,
            'vendor_id' => $this->vendor->id,
            'is_visible' => false,
        ]);
    }

    /** @test */
    public function it_can_set_product_visibility_for_group()
    {
        $visibility = $this->catalogService->setProductVisibilityForGroup($this->product, $this->vipGroup->id, true);

        $this->assertDatabaseHas('product_vendor_visibility', [
            'product_id' => $this->product->id,
            'vendor_group_id' => $this->vipGroup->id,
            'vendor_id' => null,
            'is_visible' => true,
        ]);
    }

    /** @test */
    public function it_can_bulk_set_visibility_for_vendor()
    {
        $products = Product::factory()->count(3)->create(['is_active' => true]);
        $productIds = $products->pluck('id')->toArray();

        $count = $this->catalogService->bulkSetVisibilityForVendor($productIds, $this->vendor, false);

        $this->assertEquals(3, $count);

        foreach ($products as $product) {
            $this->assertDatabaseHas('product_vendor_visibility', [
                'product_id' => $product->id,
                'vendor_id' => $this->vendor->id,
                'is_visible' => false,
            ]);
        }
    }

    /** @test */
    public function it_can_bulk_set_visibility_for_group()
    {
        $products = Product::factory()->count(3)->create(['is_active' => true]);
        $productIds = $products->pluck('id')->toArray();

        $count = $this->catalogService->bulkSetVisibilityForGroup($productIds, $this->vipGroup->id, true);

        $this->assertEquals(3, $count);

        foreach ($products as $product) {
            $this->assertDatabaseHas('product_vendor_visibility', [
                'product_id' => $product->id,
                'vendor_group_id' => $this->vipGroup->id,
                'is_visible' => true,
            ]);
        }
    }

    /** @test */
    public function it_can_remove_vendor_visibility_restrictions()
    {
        $this->catalogService->setProductVisibilityForVendor($this->product, $this->vendor, false);

        $deleted = $this->catalogService->removeVendorVisibilityRestrictions($this->product, $this->vendor);

        $this->assertEquals(1, $deleted);
        $this->assertDatabaseMissing('product_vendor_visibility', [
            'product_id' => $this->product->id,
            'vendor_id' => $this->vendor->id,
        ]);
    }

    /** @test */
    public function it_can_filter_products_by_category()
    {
        $category2 = Category::factory()->create(['is_active' => true]);
        Product::factory()->count(3)->create(['category_id' => $this->category->id, 'is_active' => true]);
        Product::factory()->count(2)->create(['category_id' => $category2->id, 'is_active' => true]);

        $products = $this->catalogService->getVisibleProductsForVendor($this->vendor, [
            'category_id' => $this->category->id,
        ])->get();

        // At least 3 products (plus the one created in setUp)
        $this->assertGreaterThanOrEqual(3, $products->count());

        foreach ($products as $product) {
            $this->assertEquals($this->category->id, $product->category_id);
        }
    }

    /** @test */
    public function it_can_search_products_by_name()
    {
        Product::factory()->create(['name_fr' => 'iPhone 15', 'is_active' => true]);
        Product::factory()->create(['name_fr' => 'Samsung Galaxy', 'is_active' => true]);

        $products = $this->catalogService->getVisibleProductsForVendor($this->vendor, [
            'search' => 'iPhone',
        ])->get();

        $this->assertGreaterThanOrEqual(1, $products->count());
        $this->assertStringContainsString('iPhone', $products->first()->name_fr);
    }

    /** @test */
    public function it_can_search_products_by_sku()
    {
        Product::factory()->create(['sku' => 'APPLE-001', 'is_active' => true]);

        $products = $this->catalogService->getVisibleProductsForVendor($this->vendor, [
            'search' => 'APPLE',
        ])->get();

        $this->assertGreaterThanOrEqual(1, $products->count());
    }

    /** @test */
    public function it_can_filter_in_stock_products()
    {
        Product::factory()->create(['stock_quantity' => 0, 'is_active' => true]);
        Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);

        $products = $this->catalogService->getVisibleProductsForVendor($this->vendor, [
            'in_stock' => true,
        ])->get();

        foreach ($products as $product) {
            $this->assertGreaterThan(0, $product->stock_quantity);
        }
    }

    /** @test */
    public function it_can_sort_products_by_price()
    {
        Product::factory()->create(['base_price' => 50.000, 'is_active' => true]);
        Product::factory()->create(['base_price' => 150.000, 'is_active' => true]);
        Product::factory()->create(['base_price' => 100.000, 'is_active' => true]);

        $products = $this->catalogService->getVisibleProductsForVendor($this->vendor, [
            'sort_by' => 'price',
            'sort_order' => 'asc',
        ])->get();

        $prices = $products->pluck('base_price')->toArray();

        for ($i = 0; $i < count($prices) - 1; $i++) {
            $this->assertLessThanOrEqual($prices[$i + 1], $prices[$i]);
        }
    }

    /** @test */
    public function it_can_get_categories_for_vendor_with_product_counts()
    {
        $category2 = Category::factory()->create(['is_active' => true]);

        Product::factory()->count(3)->create(['category_id' => $this->category->id, 'is_active' => true]);
        Product::factory()->count(2)->create(['category_id' => $category2->id, 'is_active' => true]);

        $categories = $this->catalogService->getCategoriesForVendor($this->vendor);

        $this->assertGreaterThanOrEqual(2, $categories->count());

        foreach ($categories as $category) {
            $this->assertGreaterThan(0, $category->products_count);
        }
    }

    /** @test */
    public function it_excludes_categories_with_no_visible_products()
    {
        $emptyCategory = Category::factory()->create(['is_active' => true]);

        $categories = $this->catalogService->getCategoriesForVendor($this->vendor);

        $emptyCategoryFound = $categories->contains('id', $emptyCategory->id);
        $this->assertFalse($emptyCategoryFound);
    }

    /** @test */
    public function it_can_get_product_details_for_vendor()
    {
        $details = $this->catalogService->getProductDetailsForVendor($this->product, $this->vendor);

        $this->assertArrayHasKey('id', $details);
        $this->assertArrayHasKey('sku', $details);
        $this->assertArrayHasKey('name', $details);
        $this->assertArrayHasKey('base_price', $details);
        $this->assertArrayHasKey('stock_quantity', $details);
        $this->assertArrayHasKey('pricing_tiers', $details);
        $this->assertArrayHasKey('category', $details);
    }

    /** @test */
    public function it_throws_exception_when_getting_details_of_invisible_product()
    {
        $this->catalogService->setProductVisibilityForVendor($this->product, $this->vendor, false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Product not visible to vendor');

        $this->catalogService->getProductDetailsForVendor($this->product, $this->vendor);
    }

    /** @test */
    public function it_can_search_products_with_pagination()
    {
        Product::factory()->count(30)->create(['is_active' => true]);

        $results = $this->catalogService->searchProducts($this->vendor, 'Product', ['per_page' => 15]);

        $this->assertEquals(15, $results->perPage());
        $this->assertGreaterThan(1, $results->lastPage());
    }

    /** @test */
    public function different_groups_see_different_products()
    {
        // Hide product from Standard group
        $this->catalogService->setProductVisibilityForGroup($this->product, $this->standardGroup->id, false);

        // VIP vendor should see it
        $vipVisible = $this->catalogService->isProductVisible($this->product, $this->vendor);
        $this->assertTrue($vipVisible);

        // Standard vendor should not see it
        $standardVisible = $this->catalogService->isProductVisible($this->product, $this->vendor2);
        $this->assertFalse($standardVisible);
    }

    /** @test */
    public function it_excludes_explicitly_hidden_products_from_vendor()
    {
        Product::factory()->count(5)->create(['is_active' => true]);

        // Hide one product from this vendor
        $productToHide = Product::first();
        $this->catalogService->setProductVisibilityForVendor($productToHide, $this->vendor, false);

        $products = $this->catalogService->getVisibleProductsForVendor($this->vendor)->get();

        $this->assertFalse($products->contains('id', $productToHide->id));
    }
}

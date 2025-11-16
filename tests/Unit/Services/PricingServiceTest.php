<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\VendorGroup;
use App\Models\VendorProfile;
use App\Models\ProductPricing;
use App\Services\Pricing\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PricingService $pricingService;
    protected User $vendor;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pricingService = new PricingService();

        // Create vendor group
        $group = VendorGroup::create([
            'name' => 'VIP',
            'slug' => 'vip',
            'default_discount_percentage' => 10.00,
            'minimum_order_amount' => 100.000,
            'priority_level' => 10,
        ]);

        // Create vendor
        $this->vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);

        VendorProfile::create([
            'user_id' => $this->vendor->id,
            'company_name' => 'Test Company',
            'vendor_group_id' => $group->id,
            'credit_limit' => 10000.000,
            'payment_term' => 'net_30',
        ]);

        // Create product
        $this->product = Product::factory()->create([
            'sku' => 'TEST-001',
            'name_fr' => 'Produit Test',
            'base_price' => 100.000,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_calculates_base_price_without_discounts()
    {
        $result = $this->pricingService->calculatePrice($this->product, $this->vendor, 1);

        $this->assertEquals(100.000, $result['base_price']);
        $this->assertEquals(0, $result['promotion_discount']);
        $this->assertEquals(100.000, $result['final_price']);
        $this->assertEquals(100.000, $result['total']);
    }

    /** @test */
    public function it_applies_group_discount()
    {
        // Group has 10% discount, so price should be 90 TND
        $expectedPrice = 100 * 0.9; // 90.000

        $result = $this->pricingService->calculatePrice($this->product, $this->vendor, 1);

        $this->assertEquals(90.000, $result['base_price']);
        $this->assertEquals(90.000, $result['final_price']);
    }

    /** @test */
    public function it_applies_vendor_specific_pricing()
    {
        // Create vendor-specific pricing
        ProductPricing::create([
            'product_id' => $this->product->id,
            'vendor_id' => $this->vendor->id,
            'price' => 85.000,
            'discount_percentage' => 0,
            'min_quantity' => 1,
        ]);

        $result = $this->pricingService->calculatePrice($this->product, $this->vendor, 1);

        // Vendor-specific pricing should override group pricing
        $this->assertEquals(85.000, $result['base_price']);
        $this->assertEquals(85.000, $result['final_price']);
    }

    /** @test */
    public function it_applies_volume_pricing()
    {
        // Create volume pricing: 10+ units = 80 TND, 50+ units = 75 TND
        ProductPricing::create([
            'product_id' => $this->product->id,
            'vendor_id' => $this->vendor->id,
            'price' => 80.000,
            'min_quantity' => 10,
            'max_quantity' => 49,
        ]);

        ProductPricing::create([
            'product_id' => $this->product->id,
            'vendor_id' => $this->vendor->id,
            'price' => 75.000,
            'min_quantity' => 50,
        ]);

        // Test with 15 units (should get 80 TND)
        $result = $this->pricingService->calculatePrice($this->product, $this->vendor, 15);
        $this->assertEquals(80.000, $result['base_price']);

        // Test with 50 units (should get 75 TND)
        $result = $this->pricingService->calculatePrice($this->product, $this->vendor, 50);
        $this->assertEquals(75.000, $result['base_price']);
    }

    /** @test */
    public function it_calculates_cart_total()
    {
        $product2 = Product::factory()->create([
            'sku' => 'TEST-002',
            'name_fr' => 'Produit Test 2',
            'base_price' => 50.000,
            'stock_quantity' => 100,
            'is_active' => true,
        ]);

        $cartItems = [
            ['product_id' => $this->product->id, 'quantity' => 2],
            ['product_id' => $product2->id, 'quantity' => 3],
        ];

        $result = $this->pricingService->calculateCartTotal($this->vendor, $cartItems);

        // Product 1: 100 TND * 0.9 (group discount) * 2 = 180 TND
        // Product 2: 50 TND * 0.9 (group discount) * 3 = 135 TND
        // Total: 315 TND
        $this->assertEquals(315.000, $result['total']);
        $this->assertTrue($result['meets_minimum']);
    }

    /** @test */
    public function it_throws_exception_for_non_vendor_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User must be a vendor');

        $this->pricingService->calculatePrice($this->product, $admin, 1);
    }

    /** @test */
    public function it_gets_pricing_tiers()
    {
        ProductPricing::create([
            'product_id' => $this->product->id,
            'vendor_id' => $this->vendor->id,
            'price' => 90.000,
            'min_quantity' => 1,
            'max_quantity' => 9,
        ]);

        ProductPricing::create([
            'product_id' => $this->product->id,
            'vendor_id' => $this->vendor->id,
            'price' => 85.000,
            'min_quantity' => 10,
            'max_quantity' => 49,
        ]);

        ProductPricing::create([
            'product_id' => $this->product->id,
            'vendor_id' => $this->vendor->id,
            'price' => 80.000,
            'min_quantity' => 50,
        ]);

        $tiers = $this->pricingService->getPricingTiers($this->product, $this->vendor);

        $this->assertCount(3, $tiers);
        $this->assertEquals(90.000, $tiers[0]['price']);
        $this->assertEquals(85.000, $tiers[1]['price']);
        $this->assertEquals(80.000, $tiers[2]['price']);
    }
}

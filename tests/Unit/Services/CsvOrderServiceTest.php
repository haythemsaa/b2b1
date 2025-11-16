<?php

namespace Tests\Unit\Services;

use App\Models\CsvImportLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorGroup;
use App\Models\VendorProfile;
use App\Services\Catalog\CatalogService;
use App\Services\CsvOrderService;
use App\Services\Order\OrderService;
use App\Services\Pricing\PricingService;
use App\Services\Stock\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CsvOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CsvOrderService $csvOrderService;
    protected User $vendor;
    protected VendorGroup $vendorGroup;
    protected Product $product1;
    protected Product $product2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create vendor group
        $this->vendorGroup = VendorGroup::factory()->create([
            'name' => 'Standard',
            'discount_percentage' => 5.0,
        ]);

        // Create vendor
        $this->vendor = User::factory()->create(['role' => 'vendor']);
        VendorProfile::factory()->create([
            'user_id' => $this->vendor->id,
            'vendor_group_id' => $this->vendorGroup->id,
        ]);

        // Create products
        $this->product1 = Product::factory()->create([
            'sku' => 'PROD-001',
            'name' => 'Product 1',
            'base_price' => 100.000,
            'stock_quantity' => 1000,
            'is_active' => true,
        ]);

        $this->product2 = Product::factory()->create([
            'sku' => 'PROD-002',
            'name' => 'Product 2',
            'base_price' => 50.000,
            'stock_quantity' => 500,
            'is_active' => true,
        ]);

        // Initialize service
        $this->csvOrderService = new CsvOrderService(
            app(PricingService::class),
            app(StockService::class),
            app(OrderService::class),
            app(CatalogService::class)
        );

        Storage::fake('local');
    }

    /** @test */
    public function it_can_process_csv_upload()
    {
        $csvContent = "SKU,Quantity,Notes\nPROD-001,10,Test note\nPROD-002,5,";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $result = $this->csvOrderService->processCsvUpload($this->vendor, $file);

        $this->assertArrayHasKey('import_id', $result);
        $this->assertArrayHasKey('valid_rows', $result);
        $this->assertArrayHasKey('invalid_rows', $result);
        $this->assertEquals(2, $result['valid_rows']);
        $this->assertEquals(0, $result['invalid_rows']);

        // Check import log was created
        $this->assertDatabaseHas('csv_import_logs', [
            'vendor_id' => $this->vendor->id,
            'success_count' => 2,
            'error_count' => 0,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_validates_invalid_sku()
    {
        $csvContent = "SKU,Quantity\nINVALID-SKU,10\nPROD-001,5";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $result = $this->csvOrderService->processCsvUpload($this->vendor, $file);

        $this->assertEquals(1, $result['valid_rows']);
        $this->assertEquals(1, $result['invalid_rows']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('not found', $result['errors'][0]['error']);
    }

    /** @test */
    public function it_validates_insufficient_stock()
    {
        $csvContent = "SKU,Quantity\nPROD-001,2000"; // More than available

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $result = $this->csvOrderService->processCsvUpload($this->vendor, $file);

        $this->assertEquals(0, $result['valid_rows']);
        $this->assertEquals(1, $result['invalid_rows']);
        $this->assertStringContainsString('Insufficient stock', $result['errors'][0]['error']);
    }

    /** @test */
    public function it_can_create_order_from_import()
    {
        $csvContent = "SKU,Quantity\nPROD-001,10\nPROD-002,5";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $result = $this->csvOrderService->processCsvUpload($this->vendor, $file);

        $order = $this->csvOrderService->createOrderFromImport($this->vendor, $result['import_id']);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($this->vendor->id, $order->vendor_id);
        $this->assertEquals(2, $order->items()->count());
    }

    /** @test */
    public function it_can_process_quick_order()
    {
        $items = [
            ['sku' => 'PROD-001', 'quantity' => 10, 'notes' => 'Test'],
            ['sku' => 'PROD-002', 'quantity' => 5, 'notes' => ''],
        ];

        $result = $this->csvOrderService->processQuickOrder($this->vendor, $items);

        $this->assertCount(2, $result['valid_items']);
        $this->assertEmpty($result['errors']);
        $this->assertArrayHasKey('totals', $result);
    }

    /** @test */
    public function it_generates_csv_template()
    {
        $template = $this->csvOrderService->generateCsvTemplate();

        $this->assertStringContainsString('SKU,Quantity,Notes', $template);
        $this->assertStringContainsString('PROD-001,10,Urgent', $template);
    }

    /** @test */
    public function it_calculates_totals_correctly()
    {
        $csvContent = "SKU,Quantity\nPROD-001,10\nPROD-002,5";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $result = $this->csvOrderService->processCsvUpload($this->vendor, $file);

        $this->assertArrayHasKey('totals', $result);
        $this->assertEquals(2, $result['totals']['items_count']);
        $this->assertGreaterThan(0, $result['totals']['subtotal']);
        $this->assertGreaterThan(0, $result['totals']['tax']);
        $this->assertGreaterThan(0, $result['totals']['total']);
    }
}

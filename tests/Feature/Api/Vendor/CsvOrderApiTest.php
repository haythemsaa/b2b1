<?php

namespace Tests\Feature\Api\Vendor;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorGroup;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CsvOrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendor;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create vendor
        $vendorGroup = VendorGroup::factory()->create();
        $this->vendor = User::factory()->create(['role' => 'vendor']);
        VendorProfile::factory()->create([
            'user_id' => $this->vendor->id,
            'vendor_group_id' => $vendorGroup->id,
        ]);

        // Create product
        $this->product = Product::factory()->create([
            'sku' => 'PROD-TEST',
            'stock_quantity' => 1000,
            'is_active' => true,
        ]);

        Storage::fake('local');
    }

    /** @test */
    public function vendor_can_upload_csv_and_get_preview()
    {
        Sanctum::actingAs($this->vendor);

        $csvContent = "SKU,Quantity\nPROD-TEST,10";
        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->postJson('/api/vendor/orders/import-csv', [
            'file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'import_id',
                    'total_rows',
                    'valid_rows',
                    'invalid_rows',
                    'errors',
                    'preview',
                    'totals',
                ],
            ]);

        $this->assertEquals(1, $response->json('data.valid_rows'));
    }

    /** @test */
    public function vendor_can_confirm_csv_import_and_create_order()
    {
        Sanctum::actingAs($this->vendor);

        $csvContent = "SKU,Quantity\nPROD-TEST,10";
        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $uploadResponse = $this->postJson('/api/vendor/orders/import-csv', [
            'file' => $file,
        ]);

        $importId = $uploadResponse->json('data.import_id');

        $response = $this->postJson('/api/vendor/orders/confirm-csv-import', [
            'import_id' => $importId,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'order_id',
                    'order_number',
                    'items_count',
                    'total',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'vendor_id' => $this->vendor->id,
        ]);
    }

    /** @test */
    public function vendor_can_use_quick_order()
    {
        Sanctum::actingAs($this->vendor);

        $response = $this->postJson('/api/vendor/orders/quick-order', [
            'items' => [
                ['sku' => 'PROD-TEST', 'quantity' => 10, 'notes' => 'Test'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'valid_items',
                    'errors',
                    'totals',
                ],
            ]);
    }

    /** @test */
    public function vendor_can_create_order_from_quick_order()
    {
        Sanctum::actingAs($this->vendor);

        $response = $this->postJson('/api/vendor/orders/create-from-quick-order', [
            'items' => [
                ['sku' => 'PROD-TEST', 'quantity' => 10],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'order_id',
                    'order_number',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'vendor_id' => $this->vendor->id,
        ]);
    }

    /** @test */
    public function vendor_can_download_csv_template()
    {
        Sanctum::actingAs($this->vendor);

        $response = $this->get('/api/vendor/orders/csv-template');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('SKU,Quantity,Notes', $response->getContent());
    }

    /** @test */
    public function vendor_can_reorder_previous_order()
    {
        Sanctum::actingAs($this->vendor);

        // Create original order
        $originalOrder = Order::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        $originalOrder->items()->create([
            'product_id' => $this->product->id,
            'product_sku' => $this->product->sku,
            'product_name' => $this->product->name,
            'quantity' => 10,
            'unit_price' => 100.000,
            'subtotal' => 1000.000,
        ]);

        $response = $this->postJson("/api/vendor/orders/{$originalOrder->id}/reorder");

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'order_id',
                    'order_number',
                ],
            ]);

        $this->assertDatabaseCount('orders', 2);
    }

    /** @test */
    public function vendor_can_download_invoice_pdf()
    {
        Sanctum::actingAs($this->vendor);

        $order = Order::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        $response = $this->get("/api/vendor/orders/{$order->id}/invoice");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /** @test */
    public function vendor_can_export_orders_to_excel()
    {
        Sanctum::actingAs($this->vendor);

        // Create some orders
        Order::factory()->count(3)->create([
            'vendor_id' => $this->vendor->id,
        ]);

        $response = $this->get('/api/vendor/orders/export?format=xlsx');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function csv_upload_validates_file_type()
    {
        Sanctum::actingAs($this->vendor);

        $file = UploadedFile::fake()->create('test.txt', 100);

        $response = $this->postJson('/api/vendor/orders/import-csv', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function quick_order_validates_required_fields()
    {
        Sanctum::actingAs($this->vendor);

        $response = $this->postJson('/api/vendor/orders/quick-order', [
            'items' => [
                ['sku' => 'PROD-TEST'], // Missing quantity
            ],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function vendor_cannot_reorder_another_vendors_order()
    {
        Sanctum::actingAs($this->vendor);

        $otherVendor = User::factory()->create(['role' => 'vendor']);
        $order = Order::factory()->create([
            'vendor_id' => $otherVendor->id,
        ]);

        $response = $this->postJson("/api/vendor/orders/{$order->id}/reorder");

        $response->assertStatus(403);
    }

    /** @test */
    public function vendor_cannot_download_another_vendors_invoice()
    {
        Sanctum::actingAs($this->vendor);

        $otherVendor = User::factory()->create(['role' => 'vendor']);
        $order = Order::factory()->create([
            'vendor_id' => $otherVendor->id,
        ]);

        $response = $this->get("/api/vendor/orders/{$order->id}/invoice");

        $response->assertStatus(403);
    }
}

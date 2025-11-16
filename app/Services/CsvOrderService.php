<?php

namespace App\Services;

use App\Models\CsvImportLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class CsvOrderService
{
    public function __construct(
        private PricingService $pricingService,
        private StockService $stockService,
        private OrderService $orderService,
        private CatalogService $catalogService
    ) {}

    /**
     * Process CSV file upload
     */
    public function processCsvUpload(User $vendor, UploadedFile $file): array
    {
        // Store file
        $filename = 'csv_imports/' . $vendor->id . '_' . time() . '_' . $file->getClientOriginalName();
        $path = Storage::disk('local')->putFileAs('csv_imports', $file, basename($filename));

        // Create import log
        $importLog = CsvImportLog::create([
            'vendor_id' => $vendor->id,
            'filename' => $filename,
            'status' => 'processing',
        ]);

        try {
            // Read CSV file
            $data = Excel::toArray([], $file)[0];

            // Remove header row if exists
            if (count($data) > 0 && !is_numeric($data[0][0])) {
                array_shift($data);
            }

            $importLog->update(['total_rows' => count($data)]);

            // Validate and prepare items
            $results = $this->validateAndPrepareItems($vendor, $data);

            // Store valid items in the import log for later use
            $importLog->update([
                'success_count' => count($results['valid']),
                'error_count' => count($results['errors']),
                'errors' => $results['errors'],
                'valid_items' => $results['valid'],
                'status' => 'completed',
            ]);

            return [
                'import_id' => $importLog->id,
                'total_rows' => count($data),
                'valid_rows' => count($results['valid']),
                'invalid_rows' => count($results['errors']),
                'errors' => $results['errors'],
                'preview' => array_slice($results['valid'], 0, 10),
                'totals' => $this->calculateTotals($results['valid']),
            ];

        } catch (\Exception $e) {
            $importLog->update([
                'status' => 'failed',
                'errors' => [['error' => $e->getMessage()]],
            ]);

            throw $e;
        }
    }

    /**
     * Process quick order (array of SKU + quantity)
     */
    public function processQuickOrder(User $vendor, array $items): array
    {
        $data = [];
        foreach ($items as $item) {
            $data[] = [$item['sku'], $item['quantity'], $item['notes'] ?? ''];
        }

        $results = $this->validateAndPrepareItems($vendor, $data);

        return [
            'valid_items' => $results['valid'],
            'errors' => $results['errors'],
            'totals' => $this->calculateTotals($results['valid']),
        ];
    }

    /**
     * Validate and prepare items from CSV data
     */
    private function validateAndPrepareItems(User $vendor, array $rows): array
    {
        $valid = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because header + 0-index

            // Expected format: [SKU, Quantity, Notes (optional)]
            $sku = trim($row[0] ?? '');
            $quantity = $row[1] ?? null;
            $notes = $row[2] ?? null;

            // Validate basic data
            $validator = Validator::make([
                'sku' => $sku,
                'quantity' => $quantity,
            ], [
                'sku' => 'required|string',
                'quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'error' => $validator->errors()->first(),
                ];
                continue;
            }

            // Find product
            $product = Product::where('sku', $sku)
                ->where('is_active', true)
                ->first();

            if (!$product) {
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'error' => 'Product not found or not active',
                ];
                continue;
            }

            // Check visibility
            $visibleProducts = $this->catalogService->getVisibleProducts($vendor);
            if (!$visibleProducts->contains('id', $product->id)) {
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'error' => 'Product not visible for your account',
                ];
                continue;
            }

            // Check stock
            $availableStock = $this->stockService->getAvailableStock($product);
            if ($availableStock < $quantity) {
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'error' => "Insufficient stock (requested: {$quantity}, available: {$availableStock})",
                ];
                continue;
            }

            // Check minimum quantity
            if ($product->minimum_order_quantity && $quantity < $product->minimum_order_quantity) {
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'error' => "Minimum quantity is {$product->minimum_order_quantity}",
                ];
                continue;
            }

            // Check order multiple
            if ($product->order_multiple && $quantity % $product->order_multiple !== 0) {
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'error' => "Quantity must be multiple of {$product->order_multiple}",
                ];
                continue;
            }

            // Get price
            $unitPrice = $this->pricingService->calculatePrice($product, $vendor, $quantity);

            $valid[] = [
                'product_id' => $product->id,
                'sku' => $sku,
                'product_name' => $product->name,
                'quantity' => (int) $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $unitPrice * $quantity,
                'notes' => $notes,
            ];
        }

        return [
            'valid' => $valid,
            'errors' => $errors,
        ];
    }

    /**
     * Calculate totals
     */
    private function calculateTotals(array $items): array
    {
        $subtotal = collect($items)->sum('subtotal');
        $tax = $subtotal * 0.19; // TVA 19%

        return [
            'subtotal' => round($subtotal, 3),
            'tax' => round($tax, 3),
            'total' => round($subtotal + $tax, 3),
            'items_count' => count($items),
        ];
    }

    /**
     * Create order from CSV import
     */
    public function createOrderFromImport(User $vendor, int $importId): Order
    {
        $importLog = CsvImportLog::findOrFail($importId);

        if ($importLog->vendor_id !== $vendor->id) {
            throw new \Exception('Unauthorized');
        }

        if ($importLog->status !== 'completed') {
            throw new \Exception('Import not completed or failed');
        }

        if (empty($importLog->valid_items)) {
            throw new \Exception('No valid items to process');
        }

        // Convert to cart format
        $cartItems = collect($importLog->valid_items)->map(function ($item) {
            return [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ];
        })->toArray();

        // Use existing OrderService
        return $this->orderService->createOrder($vendor, $cartItems, "Created from CSV import #{$importLog->id}");
    }

    /**
     * Create order from quick order items
     */
    public function createOrderFromQuickOrder(User $vendor, array $validItems): Order
    {
        $cartItems = collect($validItems)->map(function ($item) {
            return [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ];
        })->toArray();

        return $this->orderService->createOrder($vendor, $cartItems, "Created from quick order");
    }

    /**
     * Generate CSV template
     */
    public function generateCsvTemplate(): string
    {
        $headers = ['SKU', 'Quantity', 'Notes'];
        $example = [
            ['PROD-001', '10', 'Urgent'],
            ['PROD-002', '25', ''],
            ['PROD-003', '5', 'Standard delivery'],
        ];

        $csv = implode(',', $headers) . "\n";
        foreach ($example as $row) {
            $csv .= implode(',', $row) . "\n";
        }

        return $csv;
    }

    /**
     * Parse pasted data from Excel
     */
    public function parsePastedData(string $pastedData): array
    {
        $lines = array_filter(explode("\n", trim($pastedData)));
        $data = [];

        foreach ($lines as $line) {
            $columns = preg_split('/\s+/', trim($line));
            if (count($columns) >= 2) {
                $data[] = [
                    $columns[0], // SKU
                    $columns[1], // Quantity
                    implode(' ', array_slice($columns, 2)), // Notes (rest)
                ];
            }
        }

        return $data;
    }
}

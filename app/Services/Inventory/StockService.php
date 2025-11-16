<?php

namespace App\Services\Inventory;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class StockService
{
    /**
     * Add stock to a product
     */
    public function addStock(Product $product, int $quantity, ?string $notes = null, ?Model $reference = null): StockMovement
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        return DB::transaction(function() use ($product, $quantity, $notes, $reference) {
            $quantityBefore = $product->stock_quantity;
            $quantityAfter = $quantityBefore + $quantity;

            // Update product stock
            $product->update(['stock_quantity' => $quantityAfter]);

            // Create stock movement record
            return StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Remove stock from a product
     */
    public function removeStock(Product $product, int $quantity, ?string $notes = null, ?Model $reference = null): StockMovement
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        return DB::transaction(function() use ($product, $quantity, $notes, $reference) {
            $quantityBefore = $product->stock_quantity;
            $quantityAfter = $quantityBefore - $quantity;

            if ($quantityAfter < 0 && !$product->allow_backorder) {
                throw new \Exception("Insufficient stock for product {$product->sku}. Available: {$quantityBefore}, Requested: {$quantity}");
            }

            // Update product stock
            $product->update(['stock_quantity' => max(0, $quantityAfter)]);

            // Create stock movement record
            return StockMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Adjust stock to a specific quantity
     */
    public function adjustStock(Product $product, int $newQuantity, ?string $notes = null): StockMovement
    {
        if ($newQuantity < 0) {
            throw new \InvalidArgumentException('Stock quantity cannot be negative');
        }

        return DB::transaction(function() use ($product, $newQuantity, $notes) {
            $quantityBefore = $product->stock_quantity;
            $difference = $newQuantity - $quantityBefore;

            if ($difference === 0) {
                throw new \InvalidArgumentException('New quantity is same as current quantity');
            }

            // Update product stock
            $product->update(['stock_quantity' => $newQuantity]);

            // Create stock movement record
            return StockMovement::create([
                'product_id' => $product->id,
                'type' => 'adjustment',
                'quantity' => abs($difference),
                'quantity_before' => $quantityBefore,
                'quantity_after' => $newQuantity,
                'notes' => $notes . ($difference > 0 ? " (+{$difference})" : " ({$difference})"),
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Reserve stock for an order (doesn't reduce actual stock yet)
     */
    public function reserveStock(Product $product, int $quantity, Order $order): StockMovement
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        return DB::transaction(function() use ($product, $quantity, $order) {
            $quantityBefore = $product->stock_quantity;

            // Check if sufficient stock is available
            if (!$product->allow_backorder && $quantity > $quantityBefore) {
                throw new \Exception("Insufficient stock for product {$product->sku}. Available: {$quantityBefore}, Requested: {$quantity}");
            }

            // We don't reduce stock_quantity yet, that happens when order is shipped
            // But we record the reservation
            return StockMovement::create([
                'product_id' => $product->id,
                'type' => 'reserved',
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityBefore, // No change yet
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'notes' => "Reserved for order {$order->order_number}",
                'created_by' => $order->vendor_id,
            ]);
        });
    }

    /**
     * Release reserved stock (when order is cancelled)
     */
    public function releaseStock(Product $product, int $quantity, Order $order): StockMovement
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        return DB::transaction(function() use ($product, $quantity, $order) {
            $quantityBefore = $product->stock_quantity;

            // Stock quantity doesn't change because it was never reduced
            return StockMovement::create([
                'product_id' => $product->id,
                'type' => 'released',
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityBefore, // No change
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'notes' => "Released from cancelled order {$order->order_number}",
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Confirm stock deduction (when order is shipped)
     * This converts a reservation into actual stock removal
     */
    public function confirmStockDeduction(Product $product, int $quantity, Order $order): StockMovement
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        return DB::transaction(function() use ($product, $quantity, $order) {
            $quantityBefore = $product->stock_quantity;
            $quantityAfter = $quantityBefore - $quantity;

            if ($quantityAfter < 0 && !$product->allow_backorder) {
                throw new \Exception("Insufficient stock for product {$product->sku}");
            }

            // Now we actually reduce the stock
            $product->update(['stock_quantity' => max(0, $quantityAfter)]);

            return StockMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'notes' => "Shipped with order {$order->order_number}",
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Return stock (when return is approved)
     */
    public function returnStock(Product $product, int $quantity, Model $returnRequest): StockMovement
    {
        return $this->addStock(
            $product,
            $quantity,
            "Returned from RMA #{$returnRequest->rma_number}",
            $returnRequest
        );
    }

    /**
     * Get stock movements for a product
     */
    public function getProductStockHistory(Product $product, array $filters = [])
    {
        $query = StockMovement::where('product_id', $product->id)
            ->with('creator')
            ->orderBy('created_at', 'desc');

        if (!empty($filters['type'])) {
            if (is_array($filters['type'])) {
                $query->whereIn('type', $filters['type']);
            } else {
                $query->where('type', $filters['type']);
            }
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }

        return $query;
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts(int $threshold = null)
    {
        $query = Product::active();

        if ($threshold !== null) {
            $query->where('stock_quantity', '<=', $threshold);
        } else {
            $query->lowStock();
        }

        return $query->with('category')
            ->orderBy('stock_quantity', 'asc')
            ->get();
    }

    /**
     * Get out of stock products
     */
    public function getOutOfStockProducts()
    {
        return Product::active()
            ->where('stock_quantity', 0)
            ->where('allow_backorder', false)
            ->with('category')
            ->orderBy('name_fr')
            ->get();
    }

    /**
     * Bulk import stock from array
     */
    public function bulkImportStock(array $stockData): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        DB::transaction(function() use ($stockData, &$results) {
            foreach ($stockData as $index => $item) {
                try {
                    $product = Product::where('sku', $item['sku'])->first();

                    if (!$product) {
                        $results['failed']++;
                        $results['errors'][] = "Row {$index}: Product SKU '{$item['sku']}' not found";
                        continue;
                    }

                    $quantity = (int) ($item['quantity'] ?? 0);
                    $notes = $item['notes'] ?? 'Bulk import';

                    if (isset($item['action']) && $item['action'] === 'set') {
                        $this->adjustStock($product, $quantity, $notes);
                    } elseif ($quantity > 0) {
                        $this->addStock($product, $quantity, $notes);
                    } elseif ($quantity < 0) {
                        $this->removeStock($product, abs($quantity), $notes);
                    }

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Row {$index}: {$e->getMessage()}";
                }
            }
        });

        return $results;
    }

    /**
     * Get available stock for a product (considering reservations)
     */
    public function getAvailableStock(Product $product): int
    {
        // Get total reserved stock (pending/confirmed/processing orders)
        $reservedQuantity = StockMovement::where('product_id', $product->id)
            ->where('type', 'reserved')
            ->whereHas('reference', function($q) {
                $q->whereIn('status', ['pending', 'confirmed', 'processing']);
            })
            ->sum('quantity');

        return max(0, $product->stock_quantity - $reservedQuantity);
    }

    /**
     * Check if product has sufficient available stock
     */
    public function hasAvailableStock(Product $product, int $requestedQuantity): bool
    {
        if ($product->allow_backorder) {
            return true;
        }

        return $this->getAvailableStock($product) >= $requestedQuantity;
    }

    /**
     * Get stock value for a product
     */
    public function getStockValue(Product $product): float
    {
        return round($product->stock_quantity * $product->base_price, 3);
    }

    /**
     * Get total inventory value
     */
    public function getTotalInventoryValue(): float
    {
        return Product::active()
            ->selectRaw('SUM(stock_quantity * base_price) as total_value')
            ->value('total_value') ?? 0;
    }

    /**
     * Get inventory statistics
     */
    public function getInventoryStats(): array
    {
        $totalProducts = Product::active()->count();
        $inStockProducts = Product::active()->inStock()->count();
        $lowStockProducts = Product::active()->lowStock()->count();
        $outOfStockProducts = Product::active()->where('stock_quantity', 0)->count();

        return [
            'total_products' => $totalProducts,
            'in_stock_products' => $inStockProducts,
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
            'total_stock_value' => $this->getTotalInventoryValue(),
            'in_stock_percentage' => $totalProducts > 0 ? round(($inStockProducts / $totalProducts) * 100, 2) : 0,
        ];
    }
}

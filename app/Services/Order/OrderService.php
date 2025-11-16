<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Pricing\PricingService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected PricingService $pricingService,
        protected StockService $stockService
    ) {}

    /**
     * Create a new order from cart items
     */
    public function createOrder(User $vendor, array $cartItems, array $additionalData = []): Order
    {
        if (!$vendor->isVendor()) {
            throw new \InvalidArgumentException('User must be a vendor');
        }

        // Validate cart and calculate totals
        $cartCalculation = $this->pricingService->calculateCartTotal($vendor, $cartItems);

        // Check minimum order amount
        if (!$cartCalculation['meets_minimum']) {
            throw new \Exception(
                "Order total {$cartCalculation['total']} TND does not meet minimum order amount of {$cartCalculation['minimum_order_amount']} TND"
            );
        }

        // Check vendor can place order (credit limit, etc.)
        $vendorProfile = $vendor->vendorProfile;
        if ($vendorProfile && !$vendorProfile->canPlaceOrder($cartCalculation['total'])) {
            throw new \Exception('Order exceeds credit limit or vendor cannot place orders');
        }

        return DB::transaction(function() use ($vendor, $cartItems, $cartCalculation, $additionalData) {
            // Create order
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'vendor_id' => $vendor->id,
                'status' => 'pending',
                'subtotal' => $cartCalculation['subtotal'],
                'discount_amount' => $cartCalculation['total_promotion_discount'],
                'total' => $cartCalculation['total'],
                'shipping_address' => $additionalData['shipping_address'] ?? $vendor->vendorProfile?->shipping_address,
                'billing_address' => $additionalData['billing_address'] ?? $vendor->vendorProfile?->billing_address,
                'vendor_notes' => $additionalData['notes'] ?? null,
                'is_priority' => $vendor->vendorProfile?->priority_shipping ?? false,
            ]);

            // Create order items and reserve stock
            foreach ($cartCalculation['items'] as $item) {
                $product = Product::find($item['product_id']);

                // Create order item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_sku' => $product->sku,
                    'product_name' => $product->name_fr,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['promotion_discount'] * $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Reserve stock
                $this->stockService->reserveStock($product, $item['quantity'], $order);
            }

            // Fire event
            event(new \App\Events\OrderCreated($order));

            return $order->load(['items.product', 'vendor']);
        });
    }

    /**
     * Update order status with business logic
     */
    public function updateOrderStatus(Order $order, string $newStatus, ?string $notes = null): Order
    {
        $validTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [],
            'cancelled' => [],
        ];

        if (!in_array($newStatus, $validTransitions[$order->status] ?? [])) {
            throw new \Exception("Invalid status transition from {$order->status} to {$newStatus}");
        }

        DB::transaction(function() use ($order, $newStatus, $notes) {
            $oldStatus = $order->status;

            // Update order status
            $order->updateStatus($newStatus, $notes);

            // Handle stock changes based on status
            if ($newStatus === 'cancelled' && in_array($oldStatus, ['pending', 'confirmed', 'processing'])) {
                // Release reserved stock
                foreach ($order->items as $item) {
                    $this->stockService->releaseStock($item->product, $item->quantity, $order);
                }
            } elseif ($newStatus === 'shipped') {
                // Confirm stock deduction (convert reservation to actual out)
                foreach ($order->items as $item) {
                    $this->stockService->confirmStockDeduction($item->product, $item->quantity, $order);
                }
            }
        });

        return $order->fresh(['items.product', 'vendor']);
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Order $order, string $reason, bool $byVendor = true): Order
    {
        if (!$order->canBeCancelled()) {
            throw new \Exception('Order cannot be cancelled in current status');
        }

        $notes = ($byVendor ? 'Cancelled by vendor: ' : 'Cancelled by admin: ') . $reason;

        return $this->updateOrderStatus($order, 'cancelled', $notes);
    }

    /**
     * Confirm order (admin action)
     */
    public function confirmOrder(Order $order, ?string $notes = null): Order
    {
        if ($order->status !== 'pending') {
            throw new \Exception('Only pending orders can be confirmed');
        }

        return $this->updateOrderStatus($order, 'confirmed', $notes);
    }

    /**
     * Mark order as processing
     */
    public function startProcessing(Order $order, ?string $notes = null): Order
    {
        if ($order->status !== 'confirmed') {
            throw new \Exception('Only confirmed orders can be processed');
        }

        return $this->updateOrderStatus($order, 'processing', $notes);
    }

    /**
     * Ship order
     */
    public function shipOrder(Order $order, array $shippingData): Order
    {
        if ($order->status !== 'processing') {
            throw new \Exception('Only processing orders can be shipped');
        }

        $notes = "Shipped";
        if (!empty($shippingData['tracking_number'])) {
            $notes .= " - Tracking: {$shippingData['tracking_number']}";
        }
        if (!empty($shippingData['carrier'])) {
            $notes .= " - Carrier: {$shippingData['carrier']}";
        }

        return $this->updateOrderStatus($order, 'shipped', $notes);
    }

    /**
     * Deliver order
     */
    public function deliverOrder(Order $order, ?string $notes = null): Order
    {
        if ($order->status !== 'shipped') {
            throw new \Exception('Only shipped orders can be marked as delivered');
        }

        return $this->updateOrderStatus($order, 'delivered', $notes);
    }

    /**
     * Get orders for a vendor with filters
     */
    public function getVendorOrders(User $vendor, array $filters = [])
    {
        $query = Order::where('vendor_id', $vendor->id)
            ->with(['items.product'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['search'])) {
            $query->where('order_number', 'like', "%{$filters['search']}%");
        }

        return $query;
    }

    /**
     * Get all orders (admin) with filters
     */
    public function getAllOrders(array $filters = [])
    {
        $query = Order::with(['vendor', 'items.product'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['search'])) {
            $query->where('order_number', 'like', "%{$filters['search']}%");
        }

        if (isset($filters['is_priority']) && $filters['is_priority']) {
            $query->priority();
        }

        return $query;
    }

    /**
     * Get order statistics for vendor
     */
    public function getVendorOrderStats(User $vendor): array
    {
        $baseQuery = Order::where('vendor_id', $vendor->id);

        return [
            'total_orders' => $baseQuery->count(),
            'pending_orders' => (clone $baseQuery)->pending()->count(),
            'processing_orders' => (clone $baseQuery)->whereIn('status', ['confirmed', 'processing', 'shipped'])->count(),
            'delivered_orders' => (clone $baseQuery)->delivered()->count(),
            'cancelled_orders' => (clone $baseQuery)->cancelled()->count(),
            'total_spent' => (clone $baseQuery)->delivered()->sum('total'),
            'average_order_value' => (clone $baseQuery)->delivered()->avg('total'),
        ];
    }

    /**
     * Get order statistics for admin
     */
    public function getAdminOrderStats(array $filters = []): array
    {
        $query = Order::query();

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return [
            'total_orders' => (clone $query)->count(),
            'pending_orders' => (clone $query)->pending()->count(),
            'confirmed_orders' => (clone $query)->confirmed()->count(),
            'processing_orders' => (clone $query)->processing()->count(),
            'shipped_orders' => (clone $query)->shipped()->count(),
            'delivered_orders' => (clone $query)->delivered()->count(),
            'cancelled_orders' => (clone $query)->cancelled()->count(),
            'total_revenue' => (clone $query)->delivered()->sum('total'),
            'average_order_value' => (clone $query)->delivered()->avg('total'),
        ];
    }

    /**
     * Generate unique order number
     */
    protected function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    /**
     * Validate cart items before order creation
     */
    public function validateCart(User $vendor, array $cartItems): array
    {
        $errors = [];
        $catalogService = app(\App\Services\Catalog\CatalogService::class);

        foreach ($cartItems as $index => $item) {
            $product = Product::find($item['product_id'] ?? null);

            if (!$product) {
                $errors[] = "Item #{$index}: Product not found";
                continue;
            }

            if (!$catalogService->isProductVisible($product, $vendor)) {
                $errors[] = "Item #{$index}: Product {$product->sku} not visible to vendor";
            }

            if (!$product->is_active) {
                $errors[] = "Item #{$index}: Product {$product->sku} is not active";
            }

            $quantity = $item['quantity'] ?? 0;

            if (!$product->canOrder($quantity)) {
                if ($quantity < $product->minimum_order_quantity) {
                    $errors[] = "Item #{$index}: Minimum quantity for {$product->sku} is {$product->minimum_order_quantity}";
                }
                if ($product->order_multiple > 1 && $quantity % $product->order_multiple !== 0) {
                    $errors[] = "Item #{$index}: Quantity for {$product->sku} must be multiple of {$product->order_multiple}";
                }
                if (!$product->allow_backorder && $quantity > $product->stock_quantity) {
                    $errors[] = "Item #{$index}: Insufficient stock for {$product->sku} (available: {$product->stock_quantity})";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

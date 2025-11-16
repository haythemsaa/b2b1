<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Get all orders
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'vendor_id', 'from_date', 'to_date', 'search', 'is_priority']);
        $perPage = $request->input('per_page', 15);

        $orders = $this->orderService
            ->getAllOrders($filters)
            ->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Get single order
     */
    public function show(Order $order)
    {
        $order->load(['items.product', 'vendor.vendorProfile']);

        return response()->json([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'vendor' => [
                'id' => $order->vendor->id,
                'name' => $order->vendor->name,
                'email' => $order->vendor->email,
                'company_name' => $order->vendor->vendorProfile?->company_name,
            ],
            'status' => $order->status,
            'status_label' => $order->getStatusLabel(),
            'subtotal' => $order->subtotal,
            'discount_amount' => $order->discount_amount,
            'total' => $order->total,
            'is_priority' => $order->is_priority,
            'shipping_address' => $order->shipping_address,
            'billing_address' => $order->billing_address,
            'vendor_notes' => $order->vendor_notes,
            'admin_notes' => $order->admin_notes,
            'created_at' => $order->created_at,
            'confirmed_at' => $order->confirmed_at,
            'shipped_at' => $order->shipped_at,
            'delivered_at' => $order->delivered_at,
            'items' => $order->items->map(fn($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_sku' => $item->product_sku,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount_amount' => $item->discount_amount,
                'subtotal' => $item->subtotal,
                'product' => $item->product ? [
                    'id' => $item->product->id,
                    'sku' => $item->product->sku,
                    'current_stock' => $item->product->stock_quantity,
                ] : null,
            ]),
        ]);
    }

    /**
     * Confirm order
     */
    public function confirm(Request $request, Order $order)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $order = $this->orderService->confirmOrder($order, $validated['notes'] ?? null);

            return response()->json([
                'message' => 'Commande confirmée avec succès',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la confirmation',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Start processing order
     */
    public function startProcessing(Request $request, Order $order)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $order = $this->orderService->startProcessing($order, $validated['notes'] ?? null);

            return response()->json([
                'message' => 'Traitement de la commande démarré',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Ship order
     */
    public function ship(Request $request, Order $order)
    {
        $validated = $request->validate([
            'tracking_number' => 'nullable|string|max:100',
            'carrier' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $order = $this->orderService->shipOrder($order, $validated);

            return response()->json([
                'message' => 'Commande expédiée avec succès',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'expédition',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Deliver order
     */
    public function deliver(Request $request, Order $order)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $order = $this->orderService->deliverOrder($order, $validated['notes'] ?? null);

            return response()->json([
                'message' => 'Commande livrée avec succès',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $order = $this->orderService->cancelOrder($order, $validated['reason'], false);

            return response()->json([
                'message' => 'Commande annulée avec succès',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'annulation',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get order statistics
     */
    public function stats(Request $request)
    {
        $filters = $request->only(['from_date', 'to_date']);
        $stats = $this->orderService->getAdminOrderStats($filters);

        return response()->json($stats);
    }

    /**
     * Update order notes
     */
    public function updateNotes(Request $request, Order $order)
    {
        $validated = $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        $order->update(['admin_notes' => $validated['admin_notes']]);

        return response()->json([
            'message' => 'Notes mises à jour avec succès',
            'order' => $order,
        ]);
    }
}

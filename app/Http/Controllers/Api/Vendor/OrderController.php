<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Exports\OrdersExport;
use App\Models\Order;
use App\Services\CsvOrderService;
use App\Services\InvoicePdfService;
use App\Services\Order\OrderService;
use App\Services\Pricing\PricingService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected PricingService $pricingService,
        protected CsvOrderService $csvOrderService,
        protected InvoicePdfService $invoicePdfService
    ) {}

    /**
     * Get all vendor orders
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'from_date', 'to_date', 'search']);
        $perPage = $request->input('per_page', 15);

        $orders = $this->orderService
            ->getVendorOrders($request->user(), $filters)
            ->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Get single order details
     */
    public function show(Request $request, Order $order)
    {
        // Ensure vendor can only see their own orders
        if ($order->vendor_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $order->load(['items.product', 'vendor']);

        return response()->json([
            'id' => $order->id,
            'order_number' => $order->order_number,
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
     * Create new order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'nullable|string',
            'billing_address' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            // Validate cart
            $validation = $this->orderService->validateCart($request->user(), $validated['items']);

            if (!$validation['valid']) {
                return response()->json([
                    'message' => 'Erreurs de validation du panier',
                    'errors' => $validation['errors'],
                ], 422);
            }

            // Create order
            $order = $this->orderService->createOrder(
                $request->user(),
                $validated['items'],
                [
                    'shipping_address' => $validated['shipping_address'] ?? null,
                    'billing_address' => $validated['billing_address'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]
            );

            return response()->json([
                'message' => 'Commande créée avec succès',
                'order' => $order,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, Order $order)
    {
        // Ensure vendor can only cancel their own orders
        if ($order->vendor_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $order = $this->orderService->cancelOrder($order, $validated['reason'], true);

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
        $stats = $this->orderService->getVendorOrderStats($request->user());

        return response()->json($stats);
    }

    /**
     * Validate cart before checkout
     */
    public function validateCart(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            // Validate cart items
            $validation = $this->orderService->validateCart($request->user(), $validated['items']);

            if (!$validation['valid']) {
                return response()->json([
                    'valid' => false,
                    'errors' => $validation['errors'],
                ], 422);
            }

            // Calculate cart total
            $cartTotal = $this->pricingService->calculateCartTotal($request->user(), $validated['items']);

            return response()->json([
                'valid' => true,
                'cart' => $cartTotal,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Calculate cart total
     */
    public function calculateCart(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $cartTotal = $this->pricingService->calculateCartTotal($request->user(), $validated['items']);

            return response()->json($cartTotal);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur de calcul',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Import orders from CSV file
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:10240', // 10MB max
        ]);

        try {
            $result = $this->csvOrderService->processCsvUpload($request->user(), $request->file('file'));

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du traitement du fichier',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Confirm CSV import and create order
     */
    public function confirmCsvImport(Request $request)
    {
        $validated = $request->validate([
            'import_id' => 'required|exists:csv_import_logs,id',
        ]);

        try {
            $order = $this->csvOrderService->createOrderFromImport(
                $request->user(),
                $validated['import_id']
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'items_count' => $order->items()->count(),
                    'total' => $order->total,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Quick order (paste from Excel or manual entry)
     */
    public function quickOrder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        try {
            $result = $this->csvOrderService->processQuickOrder($request->user(), $validated['items']);

            if (!empty($result['errors'])) {
                return response()->json([
                    'status' => 'warning',
                    'data' => $result,
                    'message' => 'Certains articles ont des erreurs',
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du traitement',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create order from quick order
     */
    public function createFromQuickOrder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $result = $this->csvOrderService->processQuickOrder($request->user(), $validated['items']);

            if (!empty($result['errors'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Certains articles ont des erreurs',
                    'errors' => $result['errors'],
                ], 422);
            }

            $order = $this->csvOrderService->createOrderFromQuickOrder(
                $request->user(),
                $result['valid_items']
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'items_count' => $order->items()->count(),
                    'total' => $order->total,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Download CSV template
     */
    public function downloadCsvTemplate()
    {
        $csv = $this->csvOrderService->generateCsvTemplate();

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="order_template.csv"');
    }

    /**
     * Reorder (duplicate previous order)
     */
    public function reorder(Request $request, Order $order)
    {
        // Ensure vendor can only reorder their own orders
        if ($order->vendor_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        try {
            // Load items
            $order->load('items');

            // Convert to cart items
            $cartItems = $order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ];
            })->toArray();

            // Validate cart (check stock, etc.)
            $validation = $this->orderService->validateCart($request->user(), $cartItems);

            if (!$validation['valid']) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Certains articles ne sont plus disponibles',
                    'errors' => $validation['errors'],
                    'valid_items' => $validation['valid_items'] ?? [],
                ], 422);
            }

            // Create new order
            $newOrder = $this->orderService->createOrder(
                $request->user(),
                $cartItems,
                "Récommande de {$order->order_number}"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Commande dupliquée avec succès',
                'data' => [
                    'order_id' => $newOrder->id,
                    'order_number' => $newOrder->order_number,
                    'items_count' => $newOrder->items()->count(),
                    'total' => $newOrder->total,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récommande',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoice(Request $request, Order $order)
    {
        // Ensure vendor can only download their own invoices
        if ($order->vendor_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        try {
            return $this->invoicePdfService->generateInvoice($order);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la génération de la facture',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Export orders to CSV/Excel
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => 'nullable|in:csv,xlsx',
            'status' => 'nullable|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $format = $validated['format'] ?? 'xlsx';
        $filename = 'commandes_' . now()->format('Y-m-d_His') . '.' . $format;

        try {
            return Excel::download(
                new OrdersExport(
                    $request->user(),
                    $validated['status'] ?? null,
                    $validated['start_date'] ?? null,
                    $validated['end_date'] ?? null
                ),
                $filename
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'export',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}

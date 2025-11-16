<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function __construct(
        protected CreditService $creditService
    ) {}

    /**
     * Get vendor's invoices
     */
    public function index(Request $request)
    {
        $vendor = Auth::user();

        $query = Invoice::forVendor($vendor->id)
            ->with(['order', 'payments'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('invoice_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('invoice_date', '<=', $request->end_date);
        }

        $invoices = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $invoices,
        ]);
    }

    /**
     * Get specific invoice
     */
    public function show(Invoice $invoice)
    {
        $vendor = Auth::user();

        if ($invoice->vendor_id !== $vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $invoice->load(['order.items', 'payments', 'reminders']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'invoice' => $invoice,
                'early_payment_info' => $this->creditService->calculateEarlyPaymentSavings($invoice),
            ],
        ]);
    }

    /**
     * Make payment on invoice
     */
    public function makePayment(Request $request, Invoice $invoice)
    {
        $vendor = Auth::user();

        if ($invoice->vendor_id !== $vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.001',
            'payment_method' => 'required|in:bank_transfer,check,cash,card,other',
            'transaction_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Validate amount
        $remainingAmount = $invoice->getRemainingAmount();
        if ($request->amount > $remainingAmount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment amount exceeds remaining balance',
                'remaining_amount' => $remainingAmount,
            ], 422);
        }

        try {
            $this->creditService->processPayment(
                $invoice,
                $request->amount,
                $request->payment_method,
                $request->transaction_reference,
                $request->notes
            );

            $invoice->refresh();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment recorded successfully',
                'data' => [
                    'invoice' => $invoice->load('payments'),
                    'remaining_amount' => $invoice->getRemainingAmount(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get credit statistics
     */
    public function creditStats()
    {
        $vendor = Auth::user();

        $stats = $this->creditService->getCreditStats($vendor);
        $overdueInvoices = $this->creditService->getOverdueInvoices($vendor);
        $upcomingInvoices = $this->creditService->getUpcomingInvoices($vendor, 7);

        return response()->json([
            'status' => 'success',
            'data' => [
                'credit' => $stats,
                'overdue_invoices' => $overdueInvoices,
                'upcoming_invoices' => $upcomingInvoices,
            ],
        ]);
    }

    /**
     * Get overdue invoices
     */
    public function overdue()
    {
        $vendor = Auth::user();

        $invoices = $this->creditService->getOverdueInvoices($vendor);

        return response()->json([
            'status' => 'success',
            'data' => $invoices,
        ]);
    }

    /**
     * Get upcoming invoices (due soon)
     */
    public function upcoming(Request $request)
    {
        $vendor = Auth::user();
        $days = $request->get('days', 7);

        $invoices = $this->creditService->getUpcomingInvoices($vendor, $days);

        return response()->json([
            'status' => 'success',
            'data' => $invoices,
        ]);
    }

    /**
     * Get payment history
     */
    public function paymentHistory(Request $request)
    {
        $vendor = Auth::user();

        $payments = Invoice::forVendor($vendor->id)
            ->with('payments')
            ->get()
            ->pluck('payments')
            ->flatten()
            ->sortByDesc('payment_date')
            ->values();

        if ($request->has('start_date')) {
            $payments = $payments->where('payment_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $payments = $payments->where('payment_date', '<=', $request->end_date);
        }

        return response()->json([
            'status' => 'success',
            'data' => $payments->values(),
        ]);
    }
}

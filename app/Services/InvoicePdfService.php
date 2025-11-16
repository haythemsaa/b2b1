<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfService
{
    /**
     * Generate PDF invoice for an order
     */
    public function generateInvoice(Order $order)
    {
        $order->load(['vendor.vendorProfile', 'items.product']);

        $data = [
            'order' => $order,
            'vendor' => $order->vendor,
            'vendorProfile' => $order->vendor->vendorProfile,
            'items' => $order->items,
            'invoiceNumber' => $this->generateInvoiceNumber($order),
            'invoiceDate' => now(),
            'dueDate' => now()->addDays(30),
        ];

        $pdf = Pdf::loadView('invoices.pdf', $data);

        return $pdf->download("invoice-{$order->order_number}.pdf");
    }

    /**
     * Generate PDF invoice and return as stream
     */
    public function generateInvoiceStream(Order $order)
    {
        $order->load(['vendor.vendorProfile', 'items.product']);

        $data = [
            'order' => $order,
            'vendor' => $order->vendor,
            'vendorProfile' => $order->vendor->vendorProfile,
            'items' => $order->items,
            'invoiceNumber' => $this->generateInvoiceNumber($order),
            'invoiceDate' => now(),
            'dueDate' => now()->addDays(30),
        ];

        $pdf = Pdf::loadView('invoices.pdf', $data);

        return $pdf->stream("invoice-{$order->order_number}.pdf");
    }

    /**
     * Generate invoice number
     */
    private function generateInvoiceNumber(Order $order): string
    {
        $year = $order->created_at->year;
        return sprintf('INV-%d-%05d', $year, $order->id);
    }
}

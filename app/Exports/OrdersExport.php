<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected User $vendor;
    protected ?string $status;
    protected ?string $startDate;
    protected ?string $endDate;

    public function __construct(User $vendor, ?string $status = null, ?string $startDate = null, ?string $endDate = null)
    {
        $this->vendor = $vendor;
        $this->status = $status;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        $query = Order::query()
            ->where('vendor_id', $this->vendor->id)
            ->with(['items.product']);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'N° Commande',
            'Date',
            'Statut',
            'Nombre Articles',
            'Sous-total HT (TND)',
            'TVA (TND)',
            'Total TTC (TND)',
            'Produits',
        ];
    }

    public function map($order): array
    {
        $products = $order->items->map(function ($item) {
            return "{$item->product->sku} ({$item->product->name}) x{$item->quantity}";
        })->join('; ');

        return [
            $order->order_number,
            $order->created_at->format('d/m/Y H:i'),
            strtoupper($order->status),
            $order->items->count(),
            number_format($order->subtotal, 3, '.', ''),
            number_format($order->tax, 3, '.', ''),
            number_format($order->total, 3, '.', ''),
            $products,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'],
                    'bold' => true,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Commandes';
    }
}

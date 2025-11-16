<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $invoiceNumber }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2563eb;
        }
        .header h1 {
            font-size: 28px;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 16px;
            color: #64748b;
        }
        .company-info {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8fafc;
            border-radius: 5px;
        }
        .company-info strong {
            display: block;
            font-size: 14px;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 10px;
        }
        .info-col.left {
            text-align: left;
        }
        .info-col.right {
            text-align: right;
        }
        .label {
            font-weight: bold;
            color: #475569;
            margin-bottom: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background-color: #2563eb;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            margin-top: 20px;
            padding: 15px;
            background-color: #f1f5f9;
            border-radius: 5px;
        }
        .totals table {
            margin: 0;
            width: 50%;
            float: right;
        }
        .totals td {
            padding: 8px;
            border: none;
        }
        .totals .total-row {
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
            border-top: 2px solid #2563eb;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 10px;
            color: #64748b;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-confirmed {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-delivered {
            background-color: #d1fae5;
            color: #065f46;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>FACTURE</h1>
        <h2>{{ $invoiceNumber }}</h2>
    </div>

    <div class="info-row">
        <div class="info-col left">
            <div class="company-info">
                <strong>B2B Wholesale Platform</strong>
                123 Avenue Habib Bourguiba<br>
                1000 Tunis, Tunisie<br>
                <strong>Tel:</strong> +216 XX XXX XXX<br>
                <strong>Email:</strong> contact@b2b-platform.tn<br>
                <strong>TVA:</strong> TN-123456789
            </div>
        </div>
        <div class="info-col right">
            <div class="label">FACTURÉ À:</div>
            <strong>{{ $vendorProfile->company_name }}</strong><br>
            {{ $vendorProfile->address ?? 'N/A' }}<br>
            @if($vendorProfile->city || $vendorProfile->postal_code)
                {{ $vendorProfile->postal_code }} {{ $vendorProfile->city }}<br>
            @endif
            <strong>TVA:</strong> {{ $vendorProfile->tax_id }}<br>
            <strong>Tel:</strong> {{ $vendorProfile->phone }}<br>
            <strong>Email:</strong> {{ $vendor->email }}
        </div>
    </div>

    <table style="margin-bottom: 20px; background-color: #f8fafc;">
        <tr>
            <td style="border: none;"><strong>Date Facture:</strong></td>
            <td style="border: none;">{{ $invoiceDate->format('d/m/Y') }}</td>
            <td style="border: none;"><strong>Date Échéance:</strong></td>
            <td style="border: none;">{{ $dueDate->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td style="border: none;"><strong>N° Commande:</strong></td>
            <td style="border: none;">{{ $order->order_number }}</td>
            <td style="border: none;"><strong>Statut:</strong></td>
            <td style="border: none;">
                <span class="badge badge-{{ $order->status }}">
                    {{ strtoupper($order->status) }}
                </span>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">N°</th>
                <th style="width: 15%;">SKU</th>
                <th style="width: 35%;">Produit</th>
                <th class="text-center" style="width: 10%;">Qté</th>
                <th class="text-right" style="width: 15%;">Prix Unit. (TND)</th>
                <th class="text-right" style="width: 15%;">Total (TND)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->product->sku }}</td>
                <td>{{ $item->product->name }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 3) }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 3) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals clearfix">
        <table>
            <tr>
                <td><strong>Sous-total HT:</strong></td>
                <td class="text-right">{{ number_format($order->subtotal, 3) }} TND</td>
            </tr>
            <tr>
                <td><strong>TVA (19%):</strong></td>
                <td class="text-right">{{ number_format($order->tax, 3) }} TND</td>
            </tr>
            <tr class="total-row">
                <td><strong>TOTAL TTC:</strong></td>
                <td class="text-right">{{ number_format($order->total, 3) }} TND</td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>

    <div class="footer">
        <p><strong>Conditions de paiement:</strong> NET 30 jours</p>
        <p>Merci pour votre confiance. Pour toute question, contactez-nous à contact@b2b-platform.tn</p>
        <p style="margin-top: 10px; font-size: 9px;">
            B2B Wholesale Platform - Tous droits réservés {{ date('Y') }}
        </p>
    </div>
</body>
</html>

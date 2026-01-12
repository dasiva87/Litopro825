<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Pedido #{{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #333;
            margin: 15mm 12mm 15mm 12mm;
        }

        @page {
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #007bff;
            margin-bottom: 10px;
            padding-bottom: 8px;
            position: relative;
            min-height: 80px;
        }

        .header-content {
            position: relative;
        }

        .company-logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 100px;
        }

        .company-logo img {
            width: 100px;
            height: auto;
            max-height: 75px;
            display: block;
        }

        .company-info {
            margin-left: 110px;
            margin-right: 180px;
        }

        .company-info h1 {
            color: #007bff;
            font-size: 16px;
            margin-bottom: 3px;
            font-weight: bold;
        }

        .company-info p {
            margin: 1px 0;
            font-size: 9px;
            color: #666;
        }

        .order-info {
            position: absolute;
            right: 0;
            top: 0;
            width: 170px;
            text-align: right;
        }

        .order-number {
            font-size: 14px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 3px;
        }

        .order-status {
            background: #28a745;
            color: white;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 8px;
            display: inline-block;
            margin-top: 2px;
        }

        .order-details {
            background: #f8f9fa;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #dee2e6;
            font-size: 9px;
        }

        .detail-section {
            margin-bottom: 6px;
        }

        .detail-section:last-child {
            margin-bottom: 0;
        }

        .detail-section h3 {
            background: #007bff;
            color: white;
            padding: 3px 6px;
            font-size: 10px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .detail-grid {
            display: table;
            width: 100%;
        }

        .detail-row {
            display: table-row;
        }

        .detail-label {
            display: table-cell;
            font-weight: bold;
            width: 25%;
            padding: 2px 4px;
            color: #495057;
        }

        .detail-value {
            display: table-cell;
            padding: 2px 4px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            border: 1px solid #dee2e6;
            font-size: 9px;
        }

        .items-table thead {
            background: #007bff;
            color: white;
        }

        .items-table th,
        .items-table td {
            padding: 4px 3px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .items-table th {
            font-weight: bold;
            font-size: 9px;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .item-type {
            background: #17a2b8;
            color: white;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 7px;
            display: inline-block;
        }

        .item-type.papel {
            background: #17a2b8;
        }

        .item-type.producto {
            background: #ffc107;
            color: #212529;
        }

        .totals {
            background: #f8f9fa;
            padding: 8px;
            margin-top: 10px;
            text-align: right;
            border: 1px solid #dee2e6;
        }

        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
            font-size: 9px;
        }

        .total-row span:first-child {
            display: table-cell;
            text-align: left;
        }

        .total-row span:last-child {
            display: table-cell;
            text-align: right;
        }

        .total-final {
            font-size: 11px;
            font-weight: bold;
            color: #007bff;
            border-top: 2px solid #007bff;
            padding-top: 4px;
            margin-top: 4px;
        }

        .notes {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 8px;
            margin-top: 10px;
            font-size: 9px;
        }

        .notes h4 {
            color: #856404;
            margin-bottom: 4px;
            font-size: 10px;
        }

        .footer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 8px;
            color: #666;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            @php
                // Usar avatar como logo (se configura en Settings → Logo/Avatar)
                $logoUrl = $company->getAvatarUrl();
                $logoBase64 = null;
                if ($logoUrl) {
                    try {
                        $imageData = base64_encode(file_get_contents($logoUrl));
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->buffer(base64_decode($imageData));
                        $logoBase64 = 'data:' . $mimeType . ';base64,' . $imageData;
                    } catch (\Exception $e) {
                        // Si falla, no mostrar logo
                        $logoBase64 = null;
                    }
                }
            @endphp
            @if($logoBase64)
            <div class="company-logo">
                <img src="{{ $logoBase64 }}" alt="{{ $company->name }}">
            </div>
            @endif
            <div class="company-info">
                <h1>{{ $company->name }}</h1>
                <p><strong>{{ $company->company_type->label() ?? 'N/A' }}</strong></p>
                @if($company->address)
                    <p>{{ $company->address }}</p>
                @endif
                @if($company->phone)
                    <p>Tel: {{ $company->phone }}</p>
                @endif
                @if($company->email)
                    <p>Email: {{ $company->email }}</p>
                @endif
            </div>
            <div class="order-info">
                <div class="order-number">ORDEN DE PEDIDO</div>
                <div class="order-number">#{{ $order->order_number }}</div>
                <div class="order-status">{{ $order->status_label }}</div>
            </div>
        </div>
    </div>

    <!-- Order Details -->
    <div class="order-details">
        <!-- Información del Proveedor -->
        <div class="detail-section">
            <h3>📦 Proveedor</h3>
            <div class="detail-grid">
                <div class="detail-row">
                    <span class="detail-label">Nombre:</span>
                    <span class="detail-value font-bold">{{ $supplier->name }}</span>
                    <span class="detail-label">Teléfono:</span>
                    <span class="detail-value">{{ $supplier->phone ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{ $supplier->email ?? 'N/A' }}</span>
                    <span class="detail-label">Dirección:</span>
                    <span class="detail-value">{{ $supplier->address ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Información de la Orden -->
        <div class="detail-section">
            <h3>📋 Detalles de la Orden</h3>
            <div class="detail-grid">
                <div class="detail-row">
                    <span class="detail-label">Fecha Orden:</span>
                    <span class="detail-value">{{ $order->order_date->format('d/m/Y') }}</span>
                    <span class="detail-label">Entrega:</span>
                    <span class="detail-value">{{ $order->expected_delivery_date ? $order->expected_delivery_date->format('d/m/Y') : 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Cotizaciones:</span>
                    <span class="detail-value font-bold">
                        @if($documents && $documents->count() > 0)
                            @foreach($documents as $doc)
                                {{ $doc->document_number }}{{ !$loop->last ? ', ' : '' }}
                            @endforeach
                        @else
                            N/A
                        @endif
                    </span>
                    <span class="detail-label">Creado por:</span>
                    <span class="detail-value">{{ $order->createdBy->name ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 35%">Descripción</th>
                <th style="width: 10%; text-align: center;">Pliegos</th>
                <th style="width: 12%; text-align: center;">Corte (cm)</th>
                <th style="width: 10%; text-align: center;">Cantidad</th>
                <th style="width: 12%; text-align: right;">P. Unit.</th>
                <th style="width: 12%; text-align: right;">Total</th>
                <th style="width: 4%; text-align: center;">Est.</th>
            </tr>
        </thead>
        <tbody>
            @php
                $purchaseOrderItems = $order->purchaseOrderItems ?? collect();
            @endphp
            @forelse($purchaseOrderItems as $index => $poItem)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <div style="font-weight: bold;">
                        {{ $poItem->paper_name ?? 'N/A' }}
                    </div>
                </td>
                <td class="text-center">{{ $poItem->sheets_quantity ? number_format($poItem->sheets_quantity, 0) : '-' }}</td>
                <td class="text-center">{{ $poItem->cut_size ?? '-' }}</td>
                <td class="text-center">{{ number_format($poItem->quantity_ordered ?? 0, 0) }}</td>
                <td class="text-right">${{ number_format($poItem->unit_price ?? 0, 2) }}</td>
                <td class="text-right font-bold">${{ number_format($poItem->total_price ?? 0, 2) }}</td>
                <td class="text-center">
                    <span style="font-size: 7px; padding: 1px 3px; border-radius: 2px;
                        @if($poItem->status === 'pending') background: #ffc107; color: #212529;
                        @elseif($poItem->status === 'confirmed') background: #17a2b8; color: white;
                        @elseif($poItem->status === 'received') background: #28a745; color: white;
                        @else background: #dc3545; color: white; @endif">
                        {{ match($poItem->status) {
                            'pending' => 'P',
                            'confirmed' => 'C',
                            'received' => 'R',
                            'cancelled' => 'X',
                            default => '-'
                        } }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 15px; color: #666;">
                    No hay items en esta orden
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>${{ number_format($order->total_amount, 2) }}</span>
        </div>
        <div class="total-row total-final">
            <span>TOTAL:</span>
            <span>${{ number_format($order->total_amount, 2) }} COP</span>
        </div>
    </div>

    <!-- Notes -->
    @if($order->notes)
    <div class="notes">
        <h4>📝 Notas Adicionales</h4>
        <p>{{ $order->notes }}</p>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Este documento fue generado automáticamente el {{ now()->format('d/m/Y H:i') }}</p>
        <p>Orden de Pedido #{{ $order->order_number }} - {{ $company->name }}</p>
    </div>
</body>
</html>
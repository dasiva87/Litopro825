<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Pedido #{{ $order->order_number }}</title>
    <style>
        @page {
            margin: 0.75in;
            size: letter;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 11pt;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #1A2752;
            padding-bottom: 10px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .header-content {
            position: relative;
            min-height: 90px;
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
            max-height: 80px;
            display: block;
        }
        .company-info {
            margin-left: 115px;
            margin-right: 180px;
        }
        .company-info h1 {
            color: #1A2752;
            font-size: 16pt;
            margin: 0 0 5px 0;
            font-weight: bold;
        }
        .company-info p {
            margin: 2px 0;
            font-size: 9pt;
            color: #666;
        }
        .document-title {
            position: absolute;
            right: 0;
            top: 0;
            width: 170px;
            text-align: right;
        }
        .document-title .doc-type {
            font-size: 14pt;
            font-weight: bold;
            color: #1A2752;
            margin-bottom: 3px;
        }
        .document-title .doc-number {
            font-size: 12pt;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .document-title .doc-status {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9pt;
            display: inline-block;
        }
        .order-details {
            background: #f8f9fa;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            font-size: 10pt;
        }
        .detail-section {
            margin-bottom: 10px;
        }
        .detail-section:last-child {
            margin-bottom: 0;
        }
        .detail-section h3 {
            background: #1A2752;
            color: white;
            padding: 4px 8px;
            font-size: 11pt;
            margin-bottom: 8px;
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
            padding: 3px 4px;
            color: #495057;
        }
        .detail-value {
            display: table-cell;
            padding: 3px 4px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 9pt;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px 6px;
            text-align: left;
            vertical-align: top;
        }
        .items-table th {
            background: #1A2752;
            color: white;
            font-weight: bold;
            font-size: 9pt;
        }
        .items-table td.number {
            text-align: right;
            white-space: nowrap;
        }
        .items-table td.center {
            text-align: center;
        }
        .items-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        .totals {
            margin-top: 15px;
            text-align: right;
            font-size: 10pt;
        }
        .total-line {
            margin: 3px 0;
        }
        .final-total {
            font-weight: bold;
            font-size: 12pt;
            border-top: 2px solid #1A2752;
            padding-top: 8px;
            margin-top: 8px;
            color: #1A2752;
        }
        .notes {
            margin-top: 20px;
            padding: 12px;
            background: #fffbeb;
            border-left: 3px solid #f59e0b;
        }
        .notes h3 {
            font-size: 11pt;
            margin: 0 0 5px 0;
            color: #92400e;
        }
        .notes p {
            font-size: 9pt;
            margin: 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 9pt;
            text-align: center;
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
        .item-status {
            font-size: 7pt;
            padding: 2px 4px;
            border-radius: 2px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <!-- Header con información de la empresa -->
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
            <div class="document-title">
                <div class="doc-type">ORDEN DE PEDIDO</div>
                <div class="doc-number">#{{ $order->order_number }}</div>
                <span class="doc-status">{{ $order->status_label }}</span>
            </div>
        </div>
    </div>

    <!-- Order Details -->
    <div class="order-details">
        <!-- Información del Proveedor -->
        <div class="detail-section">
            <h3>Proveedor</h3>
            <div class="detail-grid">
                @if($supplier)
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
                @else
                <div class="detail-row">
                    <span class="detail-value" style="color: #666;">Sin proveedor asignado</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Información de la Orden -->
        <div class="detail-section">
            <h3>Detalles de la Orden</h3>
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
                <th style="width: 10%;" class="text-center">Pliegos</th>
                <th style="width: 12%;" class="text-center">Corte (cm)</th>
                <th style="width: 10%;" class="text-center">Cantidad</th>
                <th style="width: 12%;" class="text-right">P. Unit.</th>
                <th style="width: 12%;" class="text-right">Total</th>
                <th style="width: 4%;" class="text-center">Est.</th>
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
                    <strong>{{ $poItem->paper_name ?? 'N/A' }}</strong>
                </td>
                <td class="text-center">{{ $poItem->sheets_quantity ? number_format($poItem->sheets_quantity, 0) : '-' }}</td>
                <td class="text-center">{{ $poItem->cut_size ?? '-' }}</td>
                <td class="text-center">{{ number_format($poItem->quantity_ordered ?? 0, 0) }}</td>
                <td class="text-right">${{ number_format($poItem->unit_price ?? 0, 2) }}</td>
                <td class="text-right font-bold">${{ number_format($poItem->total_price ?? 0, 2) }}</td>
                <td class="text-center">
                    <span class="item-status" style="
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
        <div class="total-line">
            <strong>Subtotal: ${{ number_format($order->total_amount, 2) }}</strong>
        </div>
        <div class="total-line final-total">
            <strong>TOTAL: ${{ number_format($order->total_amount, 2) }} COP</strong>
        </div>
    </div>

    <!-- Notes -->
    @if($order->notes)
    <div class="notes">
        <h3>Notas Adicionales</h3>
        <p>{{ $order->notes }}</p>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Generado el {{ now()->format('d/m/Y H:i') }} - {{ $company->name }}</p>
        <p>Este documento fue generado automáticamente por GrafiRed</p>
    </div>
</body>
</html>

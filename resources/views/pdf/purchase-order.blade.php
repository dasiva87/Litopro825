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
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 3px solid #007bff;
            margin-bottom: 20px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .company-info h1 {
            color: #007bff;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .company-info p {
            margin: 2px 0;
            font-size: 11px;
            color: #666;
        }

        .order-info {
            text-align: right;
        }

        .order-number {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }

        .order-status {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            display: inline-block;
        }

        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-section {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
        }

        .detail-section h3 {
            background: #007bff;
            color: white;
            margin: -15px -15px 10px -15px;
            padding: 8px 15px;
            font-size: 14px;
            border-radius: 4px 4px 0 0;
        }

        .detail-row {
            display: flex;
            margin-bottom: 8px;
        }

        .detail-label {
            font-weight: bold;
            width: 120px;
            color: #495057;
        }

        .detail-value {
            flex: 1;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            border: 1px solid #dee2e6;
        }

        .items-table thead {
            background: #007bff;
            color: white;
        }

        .items-table th,
        .items-table td {
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            font-size: 11px;
        }

        .items-table th {
            font-weight: bold;
            font-size: 11px;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .item-type {
            background: #17a2b8;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
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
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: right;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .total-final {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            border-top: 2px solid #007bff;
            padding-top: 8px;
        }

        .notes {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }

        .notes h4 {
            color: #856404;
            margin-bottom: 8px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 10px;
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
            <h3>📦 Información del Proveedor</h3>
            <div class="detail-row">
                <span class="detail-label">Proveedor:</span>
                <span class="detail-value font-bold">{{ $supplier->name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tipo:</span>
                <span class="detail-value">{{ $supplier->company_type->label() ?? 'N/A' }}</span>
            </div>
            @if($supplier->address)
            <div class="detail-row">
                <span class="detail-label">Dirección:</span>
                <span class="detail-value">{{ $supplier->address }}</span>
            </div>
            @endif
            @if($supplier->phone)
            <div class="detail-row">
                <span class="detail-label">Teléfono:</span>
                <span class="detail-value">{{ $supplier->phone }}</span>
            </div>
            @endif
            @if($supplier->email)
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value">{{ $supplier->email }}</span>
            </div>
            @endif
        </div>

        <!-- Información de la Orden -->
        <div class="detail-section">
            <h3>📋 Detalles de la Orden</h3>
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
            </div>
            <div class="detail-row">
                <span class="detail-label">Fecha de Orden:</span>
                <span class="detail-value">{{ $order->order_date->format('d/m/Y') }}</span>
            </div>
            @if($order->expected_delivery_date)
            <div class="detail-row">
                <span class="detail-label">Entrega Esperada:</span>
                <span class="detail-value font-bold">{{ $order->expected_delivery_date->format('d/m/Y') }}</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">Creado por:</span>
                <span class="detail-value">{{ $order->createdBy->name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Fecha de Creación:</span>
                <span class="detail-value">{{ $order->created_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 8%">#</th>
                <th style="width: 10%">Tipo</th>
                <th style="width: 35%">Descripción</th>
                <th style="width: 12%">Cantidad</th>
                <th style="width: 15%">Precio Unit.</th>
                <th style="width: 15%">Total</th>
                <th style="width: 5%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    @php
                        $isPaper = $item->itemable_type === 'App\Models\SimpleItem';
                    @endphp
                    <span class="item-type {{ $isPaper ? 'papel' : 'producto' }}">
                        {{ $isPaper ? 'PAPEL' : 'PRODUCTO' }}
                    </span>
                </td>
                <td>
                    <div style="font-weight: bold; margin-bottom: 4px;">
                        {{ $item->description ?? 'N/A' }}
                    </div>
                    @if($isPaper && $item->itemable)
                        <div style="font-size: 10px; color: #666;">
                            📄 {{ $item->itemable->total_sheets ?? 0 }} pliegos - Corte: {{ $item->itemable->horizontal_size }}x{{ $item->itemable->vertical_size }}cm
                        </div>
                    @elseif(!$isPaper && $item->itemable)
                        <div style="font-size: 10px; color: #666;">
                            📦 Código: {{ $item->itemable->code ?? 'N/A' }}
                        </div>
                    @endif
                </td>
                <td class="text-center">{{ number_format($item->pivot->quantity_ordered ?? 0, 0) }}</td>
                <td class="text-right">${{ number_format($item->pivot->unit_price ?? 0, 2) }}</td>
                <td class="text-right font-bold">${{ number_format($item->pivot->total_price ?? 0, 2) }}</td>
                <td class="text-center">
                    <span style="font-size: 9px; padding: 2px 4px; border-radius: 2px;
                        @if($item->pivot->status === 'pending') background: #ffc107; color: #212529;
                        @elseif($item->pivot->status === 'confirmed') background: #17a2b8; color: white;
                        @elseif($item->pivot->status === 'received') background: #28a745; color: white;
                        @else background: #dc3545; color: white; @endif">
                        {{ match($item->pivot->status) {
                            'pending' => 'Pendiente',
                            'confirmed' => 'Confirmado',
                            'received' => 'Recibido',
                            'cancelled' => 'Cancelado',
                            default => $item->pivot->status
                        } }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px; color: #666;">
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
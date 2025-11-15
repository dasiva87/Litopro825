<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Producción #{{ $order->production_number }}</title>
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
            border-bottom: 2px solid #6366f1;
            margin-bottom: 10px;
            padding-bottom: 8px;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .company-info {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .company-info h1 {
            color: #6366f1;
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
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }

        .order-number {
            font-size: 14px;
            font-weight: bold;
            color: #6366f1;
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
            background: #6366f1;
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
            background: #6366f1;
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

        .summary-box {
            background: #f8f9fa;
            padding: 8px;
            margin-top: 10px;
            border: 1px solid #dee2e6;
            font-size: 9px;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-row {
            display: table-row;
        }

        .summary-label {
            display: table-cell;
            font-weight: bold;
            width: 50%;
            padding: 3px 6px;
            color: #495057;
        }

        .summary-value {
            display: table-cell;
            padding: 3px 6px;
            text-align: right;
            font-size: 11px;
            color: #6366f1;
            font-weight: bold;
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

        .progress-bar {
            background: #e9ecef;
            height: 12px;
            border-radius: 3px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            background: #28a745;
            height: 100%;
            line-height: 12px;
            color: white;
            text-align: center;
            font-size: 8px;
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
                <div class="order-number">ORDEN DE PRODUCCIÓN</div>
                <div class="order-number">#{{ $order->production_number }}</div>
                <div class="order-status">{{ $order->status->getLabel() }}</div>
            </div>
        </div>
    </div>

    <!-- Order Details -->
    <div class="order-details">
        <!-- Información de Producción -->
        <div class="detail-section">
            <h3>⚙️ Detalles de Producción</h3>
            <div class="detail-grid">
                <div class="detail-row">
                    <span class="detail-label">Fecha Programada:</span>
                    <span class="detail-value font-bold">{{ $order->scheduled_date ? $order->scheduled_date->format('d/m/Y') : 'Sin programar' }}</span>
                    <span class="detail-label">Estado:</span>
                    <span class="detail-value">{{ $order->status->getLabel() }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Operador:</span>
                    <span class="detail-value">{{ $operator?->name ?? 'Sin asignar' }}</span>
                    <span class="detail-label">Proveedor:</span>
                    <span class="detail-value">{{ $supplier?->name ?? 'Sin asignar' }}</span>
                </div>
            </div>
        </div>

        <!-- Fechas de Ejecución -->
        @if($order->started_at || $order->completed_at)
        <div class="detail-section">
            <h3>📅 Tiempos de Ejecución</h3>
            <div class="detail-grid">
                <div class="detail-row">
                    <span class="detail-label">Inicio:</span>
                    <span class="detail-value">{{ $order->started_at ? $order->started_at->format('d/m/Y H:i') : 'No iniciado' }}</span>
                    <span class="detail-label">Finalización:</span>
                    <span class="detail-value">{{ $order->completed_at ? $order->completed_at->format('d/m/Y H:i') : 'No completado' }}</span>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 45%">Descripción</th>
                <th style="width: 15%; text-align: center;">Cantidad</th>
                <th style="width: 15%; text-align: center;">Millares</th>
                <th style="width: 20%; text-align: center;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @php
                $items = $order->documentItems ?? collect();
            @endphp
            @forelse($items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <div style="font-weight: bold;">
                        @if($item->itemable)
                            {{ $item->itemable->description ?? $item->description ?? 'N/A' }}
                        @else
                            {{ $item->description ?? 'N/A' }}
                        @endif
                    </div>
                    @if($item->document)
                        <div style="font-size: 8px; color: #666;">
                            Documento: {{ $item->document->document_number ?? 'N/A' }}
                        </div>
                    @endif
                </td>
                <td class="text-center">{{ number_format($item->pivot->quantity ?? 0, 0) }}</td>
                <td class="text-center">
                    @if(isset($item->itemable->total_impressions))
                        {{ number_format($item->itemable->total_impressions / 1000, 2) }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-center">
                    <span style="font-size: 8px; padding: 2px 4px; border-radius: 2px; background: #e9ecef;">
                        {{ $item->pivot->status ?? 'Pendiente' }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center" style="padding: 15px; color: #666;">
                    No hay items en esta orden de producción
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Summary -->
    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-row">
                <span class="summary-label">Total Items:</span>
                <span class="summary-value">{{ number_format($order->total_items ?? 0, 0) }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Total Millares:</span>
                <span class="summary-value">{{ number_format($order->total_impressions ?? 0, 2) }} M</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Horas Estimadas:</span>
                <span class="summary-value">{{ number_format($order->estimated_hours ?? 0, 1) }} h</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Progreso:</span>
                <span class="summary-value">{{ $order->getProgressPercentage() }}%</span>
            </div>
        </div>
    </div>

    <!-- Notes -->
    @if($order->notes)
    <div class="notes">
        <h4>📝 Notas de Producción</h4>
        <p>{{ $order->notes }}</p>
    </div>
    @endif

    @if($order->operator_notes)
    <div class="notes" style="margin-top: 5px;">
        <h4>👤 Notas del Operador</h4>
        <p>{{ $order->operator_notes }}</p>
    </div>
    @endif

    <!-- Quality Check -->
    @if($order->quality_checked)
    <div class="order-details" style="margin-top: 10px; background: #d4edda; border-color: #c3e6cb;">
        <div class="detail-section">
            <h3 style="background: #28a745;">✓ Control de Calidad</h3>
            <div class="detail-grid">
                <div class="detail-row">
                    <span class="detail-label">Revisado por:</span>
                    <span class="detail-value">{{ $order->qualityCheckedBy?->name ?? 'N/A' }}</span>
                    <span class="detail-label">Fecha:</span>
                    <span class="detail-value">{{ $order->quality_checked_at ? $order->quality_checked_at->format('d/m/Y H:i') : 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Este documento fue generado automáticamente el {{ now()->format('d/m/Y H:i') }}</p>
        <p>Orden de Producción #{{ $order->production_number }} - {{ $company->name }}</p>
    </div>
</body>
</html>

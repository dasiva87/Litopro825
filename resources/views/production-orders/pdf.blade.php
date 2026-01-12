<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $productionOrder->production_number }} - Orden de Producción</title>
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
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
            page-break-inside: avoid;
            overflow: hidden;
        }
        .header-content {
            position: relative;
            min-height: 100px;
            margin-bottom: 15px;
        }
        .company-logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 120px;
        }
        .company-logo img {
            width: 120px;
            height: auto;
            max-height: 90px;
            display: block;
        }
        .company-info {
            text-align: right;
            margin-left: 140px;
        }
        .company-info h1 {
            font-size: 20pt;
            margin: 0 0 10px 0;
            color: #dc2626;
        }
        .company-info p {
            margin: 3px 0;
            font-size: 10pt;
        }
        .document-info {
            background: #fef2f2;
            padding: 10px;
            border: 1px solid #fca5a5;
            margin-bottom: 15px;
        }
        .document-info h2 {
            font-size: 16pt;
            margin: 0 0 8px 0;
            color: #dc2626;
        }
        .info-grid {
            display: table;
            width: 100%;
            font-size: 10pt;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 30%;
            padding: 3px 4px;
        }
        .info-value {
            display: table-cell;
            padding: 3px 4px;
        }
        .operator-info {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            padding: 10px;
            background: #f9fafb;
        }
        .operator-info h3 {
            font-size: 12pt;
            margin: 0 0 8px 0;
            color: #dc2626;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: bold;
        }
        .status-draft { background: #e5e7eb; color: #374151; }
        .status-queued { background: #fef3c7; color: #92400e; }
        .status-in-progress { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

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
            background: #dc2626;
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
        .summary-box {
            background: #fef2f2;
            border: 2px solid #dc2626;
            padding: 12px;
            margin-top: 20px;
            font-size: 11pt;
        }
        .summary-box h3 {
            margin: 0 0 10px 0;
            color: #dc2626;
            font-size: 12pt;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #fca5a5;
        }
        .summary-item:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 12pt;
            padding-top: 8px;
        }
        .notes {
            margin-top: 20px;
            padding: 10px;
            background: #fffbeb;
            border: 1px solid #fbbf24;
        }
        .notes h3 {
            margin: 0 0 8px 0;
            color: #92400e;
            font-size: 11pt;
        }
        .notes p {
            margin: 0;
            font-size: 10pt;
            white-space: pre-wrap;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 9pt;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header con información de la empresa -->
    <div class="header">
        <div class="header-content">
            @php
                // Usar avatar como logo (se configura en Settings → Logo/Avatar)
                $logoUrl = $productionOrder->company->getAvatarUrl();
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
                <img src="{{ $logoBase64 }}" alt="{{ $productionOrder->company->name }}">
            </div>
            @endif
            <div class="company-info">
                <h1>{{ $productionOrder->company->name }}</h1>
                @if($productionOrder->company->tax_id)
                    <p><strong>NIT:</strong> {{ $productionOrder->company->tax_id }}</p>
                @endif
                @if($productionOrder->company->address)
                    <p>{{ $productionOrder->company->address }}</p>
                @endif
                @if($productionOrder->company->city || $productionOrder->company->state)
                    <p>{{ $productionOrder->company->city }}@if($productionOrder->company->city && $productionOrder->company->state), @endif{{ $productionOrder->company->state }}</p>
                @endif
                @if($productionOrder->company->phone)
                    <p><strong>Tel:</strong> {{ $productionOrder->company->phone }}</p>
                @endif
                @if($productionOrder->company->email)
                    <p><strong>Email:</strong> {{ $productionOrder->company->email }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Información del documento -->
    <div class="document-info">
        <h2>ORDEN DE PRODUCCIÓN #{{ $productionOrder->production_number }}</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Estado:</div>
                <div class="info-value">
                    <span class="status-badge status-{{ strtolower(str_replace('_', '-', $productionOrder->status->value)) }}">
                        {{ $productionOrder->status->getLabel() }}
                    </span>
                </div>
            </div>
            @if($productionOrder->scheduled_date)
            <div class="info-row">
                <div class="info-label">Fecha Programada:</div>
                <div class="info-value">{{ $productionOrder->scheduled_date->format('d/m/Y') }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Fecha de Creación:</div>
                <div class="info-value">{{ $productionOrder->created_at->format('d/m/Y H:i') }}</div>
            </div>
            @if($productionOrder->started_at)
            <div class="info-row">
                <div class="info-label">Iniciado:</div>
                <div class="info-value">{{ $productionOrder->started_at->format('d/m/Y H:i') }}</div>
            </div>
            @endif
            @if($productionOrder->completed_at)
            <div class="info-row">
                <div class="info-label">Completado:</div>
                <div class="info-value">{{ $productionOrder->completed_at->format('d/m/Y H:i') }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Información del operador/proveedor -->
    <div class="operator-info">
        <h3>Asignación</h3>
        <div class="info-grid">
            @if($productionOrder->operator)
            <div class="info-row">
                <div class="info-label">Operador:</div>
                <div class="info-value"><strong>{{ $productionOrder->operator->name }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $productionOrder->operator->email ?? 'No especificado' }}</div>
            </div>
            @elseif($productionOrder->supplierCompany)
            <div class="info-row">
                <div class="info-label">Proveedor (Empresa):</div>
                <div class="info-value"><strong>{{ $productionOrder->supplierCompany->name }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $productionOrder->supplierCompany->email ?? 'No especificado' }}</div>
            </div>
            @elseif($productionOrder->supplier)
            <div class="info-row">
                <div class="info-label">Proveedor:</div>
                <div class="info-value"><strong>{{ $productionOrder->supplier->name }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $productionOrder->supplier->email ?? 'No especificado' }}</div>
            </div>
            @else
            <div class="info-row">
                <div class="info-label">Estado:</div>
                <div class="info-value"><em>Sin asignar</em></div>
            </div>
            @endif
        </div>
    </div>

    <!-- Tabla de items -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 40%">Descripción</th>
                <th style="width: 12%" class="center">Cantidad</th>
                <th style="width: 12%" class="center">Impresiones</th>
                <th style="width: 18%">Documento</th>
                <th style="width: 13%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productionOrder->documentItems as $index => $item)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->itemable->name ?? 'Item' }}</strong>
                    @if($item->itemable && $item->itemable->description)
                        <br><small style="color: #666;">{{ Str::limit($item->itemable->description, 80) }}</small>
                    @endif
                </td>
                <td class="center">{{ number_format($item->pivot->quantity ?? $item->quantity, 0) }}</td>
                <td class="center">
                    {{ number_format($item->pivot->impressions ?? 0, 0) }}
                </td>
                <td>
                    @if($item->document)
                        {{ $item->document->document_number ?? 'N/A' }}
                    @else
                        <em>Sin documento</em>
                    @endif
                </td>
                <td>
                    <small>{{ ucfirst($item->pivot->status ?? 'pending') }}</small>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Resumen -->
    <div class="summary-box">
        <h3>Resumen de Producción</h3>
        <div class="summary-item">
            <span>Total de Items:</span>
            <span><strong>{{ $productionOrder->total_items ?? 0 }}</strong></span>
        </div>
        @if($productionOrder->total_impressions)
        <div class="summary-item">
            <span>Total de Impresiones:</span>
            <span><strong>{{ number_format($productionOrder->total_impressions, 0) }}</strong></span>
        </div>
        @endif
        @if($productionOrder->estimated_hours)
        <div class="summary-item">
            <span>Horas Estimadas:</span>
            <span><strong>{{ number_format($productionOrder->estimated_hours, 1) }} h</strong></span>
        </div>
        @endif
    </div>

    <!-- Notas -->
    @if($productionOrder->notes)
    <div class="notes">
        <h3>Notas de Producción</h3>
        <p>{{ $productionOrder->notes }}</p>
    </div>
    @endif

    @if($productionOrder->operator_notes)
    <div class="notes">
        <h3>Notas del Operador</h3>
        <p>{{ $productionOrder->operator_notes }}</p>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
        <p>Orden de Producción #{{ $productionOrder->production_number }}</p>
    </div>
</body>
</html>

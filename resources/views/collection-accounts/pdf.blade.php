<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $collectionAccount->account_number }} - Cuenta de Cobro</title>
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
            color: #1e40af;
        }
        .company-info p {
            margin: 3px 0;
            font-size: 10pt;
        }
        .document-info {
            background: #f8f9fa;
            padding: 8px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }
        .document-info h2 {
            font-size: 14pt;
            margin: 0 0 5px 0;
            color: #1e40af;
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
            width: 25%;
            padding: 2px 4px;
        }
        .info-value {
            display: table-cell;
            padding: 2px 4px;
        }
        .client-info {
            margin-bottom: 10px;
            border: 1px solid #ddd;
            padding: 8px;
            background: #fafafa;
        }
        .client-info h3 {
            font-size: 11pt;
            margin: 0 0 5px 0;
            color: #1e40af;
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
            background: #1e40af;
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
        .final-total {
            font-weight: bold;
            font-size: 14pt;
            border-top: 2px solid #333;
            padding-top: 8px;
            margin-top: 8px;
            color: #1e40af;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 3px;
            font-size: 9pt;
            font-weight: bold;
        }
        .status-draft { background: #e5e7eb; color: #374151; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 9pt;
            text-align: center;
            color: #666;
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
        }
        .notes p {
            font-size: 9pt;
            margin: 0;
        }
    </style>
</head>
<body>
    <!-- Header con información de la empresa -->
    <div class="header">
        <div class="header-content">
            @php
                // Usar avatar como logo (se configura en Settings → Logo/Avatar)
                $logoUrl = $collectionAccount->company->getAvatarUrl();
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
                <img src="{{ $logoBase64 }}" alt="{{ $collectionAccount->company->name }}">
            </div>
            @endif
            <div class="company-info">
                <h1>{{ $collectionAccount->company->name }}</h1>
                @if($collectionAccount->company->email)
                    <p><strong>Email:</strong> {{ $collectionAccount->company->email }}</p>
                @endif
                @if($collectionAccount->company->phone)
                    <p><strong>Teléfono:</strong> {{ $collectionAccount->company->phone }}</p>
                @endif
                @if($collectionAccount->company->address)
                    <p><strong>Dirección:</strong> {{ $collectionAccount->company->address }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Información del documento -->
    <div class="document-info">
        <h2>CUENTA DE COBRO</h2>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Número:</span>
                <span class="info-value">{{ $collectionAccount->account_number }}</span>
                <span class="info-label">Estado:</span>
                <span class="info-value">
                    <span class="status-badge status-{{ $collectionAccount->status->value }}">
                        {{ $collectionAccount->status->getLabel() }}
                    </span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha Emisión:</span>
                <span class="info-value">{{ $collectionAccount->issue_date->format('d/m/Y') }}</span>
                <span class="info-label">Fecha Vencimiento:</span>
                <span class="info-value">{{ $collectionAccount->due_date ? $collectionAccount->due_date->format('d/m/Y') : 'N/A' }}</span>
            </div>
            @if($collectionAccount->paid_date)
            <div class="info-row">
                <span class="info-label">Fecha de Pago:</span>
                <span class="info-value">{{ $collectionAccount->paid_date->format('d/m/Y') }}</span>
                <span class="info-label"></span>
                <span class="info-value"></span>
            </div>
            @endif
        </div>
    </div>

    <!-- Información del cliente -->
    <div class="client-info">
        <h3>CLIENTE</h3>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Nombre:</span>
                <span class="info-value"><strong>{{ $collectionAccount->clientCompany->name }}</strong></span>
                <span class="info-label">Teléfono:</span>
                <span class="info-value">{{ $collectionAccount->clientCompany->phone ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ $collectionAccount->clientCompany->email ?? 'N/A' }}</span>
                <span class="info-label">Dirección:</span>
                <span class="info-value">{{ $collectionAccount->clientCompany->address ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <!-- Tabla de items -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Descripción</th>
                <th style="width: 15%;">Cotización</th>
                <th style="width: 10%;" class="center">Cantidad</th>
                <th style="width: 12%;" class="number">Precio Unit.</th>
                <th style="width: 13%;" class="number">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($collectionAccount->documentItems as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->document ? $item->document->document_number : 'N/A' }}</td>
                    <td class="center">{{ number_format($item->pivot->quantity_ordered, 2) }}</td>
                    <td class="number">${{ number_format($item->pivot->unit_price, 2) }}</td>
                    <td class="number">${{ number_format($item->pivot->total_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totales -->
    <div class="totals">
        <div class="final-total">
            TOTAL: ${{ number_format($collectionAccount->total_amount, 2) }}
        </div>
    </div>

    <!-- Notas (si existen) -->
    @if($collectionAccount->notes)
        <div class="notes">
            <h3>Observaciones:</h3>
            <p>{{ $collectionAccount->notes }}</p>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Generado el {{ now()->format('d/m/Y H:i') }} - {{ $collectionAccount->company->name }}</p>
        <p>Este documento fue generado automáticamente por GrafiRed</p>
    </div>
</body>
</html>

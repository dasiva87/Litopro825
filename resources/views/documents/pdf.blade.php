<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $document->number }} - {{ $document->documentType->name }}</title>
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
            border-bottom: 2px solid #007bff;
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
            color: #007bff;
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
            color: #007bff;
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
        .document-info {
            background: #f8f9fa;
            padding: 8px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }
        .document-info h2 {
            font-size: 14pt;
            margin: 0 0 5px 0;
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
        .contact-info {
            margin-bottom: 10px;
            border: 1px solid #ddd;
            padding: 8px;
            background: #fafafa;
        }
        .contact-info h3 {
            font-size: 11pt;
            margin: 0 0 5px 0;
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
            background: #f8f9fa;
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
            border-top: 2px solid #333;
            padding-top: 8px;
            margin-top: 8px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 8pt;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .item-details {
            font-size: 8pt;
            color: #666;
            line-height: 1.2;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @php
                // Usar avatar como logo (se configura en Settings → Logo/Avatar)
                $logoUrl = $document->company->getAvatarUrl();
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
                <img src="{{ $logoBase64 }}" alt="{{ $document->company->name }}">
            </div>
            @endif
            <div class="company-info">
                <h1>{{ $document->company->name ?? 'Empresa' }}</h1>
                @if($document->company->address)
                    <p>{{ $document->company->address }}</p>
                @endif
                @if($document->company->phone)
                    <p>Tel: {{ $document->company->phone }}</p>
                @endif
                @if($document->company->email)
                    <p>Email: {{ $document->company->email }}</p>
                @endif
            </div>
            <div class="document-title">
                <div class="doc-type">{{ $document->documentType->name ?? 'COTIZACIÓN' }}</div>
                <div class="doc-number">#{{ $document->document_number }}</div>
                <div class="doc-status">{{ ucfirst($document->status) }}</div>
            </div>
        </div>
    </div>

    <div class="document-info">
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Fecha:</span>
                <span class="info-value">{{ $document->date->format('d/m/Y') }}</span>
                <span class="info-label">Válido hasta:</span>
                <span class="info-value">{{ $document->valid_until ? $document->valid_until->format('d/m/Y') : 'N/A' }}</span>
            </div>
        </div>
    </div>

    <div class="contact-info">
        <h3>CLIENTE</h3>
        @if($document->contact)
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Nombre:</span>
                    <span class="info-value"><strong>{{ $document->contact->name }}</strong></span>
                    <span class="info-label">Teléfono:</span>
                    <span class="info-value">{{ $document->contact->phone ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $document->contact->email ?? 'N/A' }}</span>
                    <span class="info-label">Dirección:</span>
                    <span class="info-value">{{ $document->contact->address ?? 'N/A' }}</span>
                </div>
            </div>
        @endif
    </div>

    @if($document->items && $document->items->count() > 0)
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Descripción</th>
                <th style="width: 15%;">Cantidad</th>
                <th style="width: 17%;">Precio Unitario</th>
                <th style="width: 18%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $item)
            @php
                // Calcular precios correctos para mostrar en PDF
                $unitPrice = 0;
                $totalPrice = 0;
                
                if ($item->itemable_type === 'App\\Models\\SimpleItem' && $item->itemable) {
                    $totalPrice = $item->itemable->final_price ?? 0;
                    $unitPrice = $item->itemable->quantity > 0 ? $totalPrice / $item->itemable->quantity : 0;
                } elseif ($item->itemable_type === 'App\\Models\\TalonarioItem' && $item->itemable) {
                    $totalPrice = $item->itemable->final_price ?? 0;
                    $unitPrice = $item->itemable->quantity > 0 ? $totalPrice / $item->itemable->quantity : 0;
                } elseif ($item->itemable_type === 'App\\Models\\MagazineItem' && $item->itemable) {
                    $totalPrice = $item->itemable->final_price ?? 0;
                    $unitPrice = $item->itemable->quantity > 0 ? $totalPrice / $item->itemable->quantity : 0;
                } elseif ($item->itemable_type === 'App\\Models\\CustomItem' && $item->itemable) {
                    $unitPrice = $item->itemable->unit_price ?? 0;
                    $totalPrice = $item->itemable->total_price ?? 0;
                } elseif ($item->itemable_type === 'App\\Models\\DigitalItem' && $item->itemable) {
                    // Para DigitalItems, usar método del DocumentItem que incluye acabados
                    $totalPrice = $item->getTotalPriceWithFinishings() ?? 0;
                    $unitPrice = $item->getUnitPriceWithFinishings() ?? 0;
                } else {
                    // Fallback a los valores del DocumentItem
                    $unitPrice = $item->unit_price ?? 0;
                    $totalPrice = $item->total_price ?? 0;
                }
            @endphp
            <tr>
                <td>{{ $item->description }}</td>
                <td class="center">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                <td class="number">${{ number_format($unitPrice, 2, ',', '.') }}</td>
                <td class="number">${{ number_format($totalPrice, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="total-line">
            <strong>Subtotal: ${{ number_format($document->subtotal, 2, ',', '.') }}</strong>
        </div>
        @if($document->tax_amount > 0)
        <div class="total-line">
            <strong>Impuestos ({{ number_format($document->tax_percentage, 1) }}%): ${{ number_format($document->tax_amount, 2, ',', '.') }}</strong>
        </div>
        @endif
        @if($document->discount_amount > 0)
        <div class="total-line">
            <strong>Descuento: -${{ number_format($document->discount_amount, 2, ',', '.') }}</strong>
        </div>
        @endif
        <div class="total-line final-total">
            <strong>TOTAL: ${{ number_format($document->total, 2, ',', '.') }}</strong>
        </div>
    </div>
    @endif

    @if($document->notes)
    <div style="margin-top: 20px;">
        <h3>Observaciones:</h3>
        <div style="background: #f8f9fa; padding: 10px; border-left: 3px solid #007bff; font-size: 10pt;">
            {{ $document->notes }}
        </div>
    </div>
    @endif
    
    @if($document->status === 'draft')
    <div style="margin-top: 20px; text-align: center; color: #dc3545; font-weight: bold; font-size: 10pt;">
        ⚠️ DOCUMENTO BORRADOR - SIN VALOR COMERCIAL
    </div>
    @endif

    <div class="footer">
        <p>Generado el {{ now()->format('d/m/Y H:i') }} - {{ $document->company->name }}</p>
        <p>Este documento fue generado automáticamente por GrafiRed</p>
    </div>
</body>
</html>
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
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .company-info {
            text-align: right;
            margin-bottom: 15px;
        }
        .company-info h1 {
            font-size: 20pt;
            margin: 0 0 10px 0;
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
        <div class="company-info">
            <h1>{{ $document->company->name ?? 'Empresa' }}</h1>
            @if($document->company->address)
                <p>{{ $document->company->address }}</p>
            @endif
            @if($document->company->phone || $document->company->email)
                <p>
                    @if($document->company->phone){{ $document->company->phone }}@endif
                    @if($document->company->phone && $document->company->email) | @endif
                    @if($document->company->email){{ $document->company->email }}@endif
                </p>
            @endif
        </div>
        
        <div class="document-info">
            <h2>{{ $document->documentType->name ?? 'Documento' }}</h2>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Número:</span>
                    <span class="info-value">{{ $document->document_number }}</span>
                    <span class="info-label">Estado:</span>
                    <span class="info-value">{{ ucfirst($document->status) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha:</span>
                    <span class="info-value">{{ $document->date->format('d/m/Y') }}</span>
                    <span class="info-label">Válido hasta:</span>
                    <span class="info-value">{{ $document->valid_until ? $document->valid_until->format('d/m/Y') : 'N/A' }}</span>
                </div>
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
                <th style="width: 35%;">Descripción</th>
                <th style="width: 25%;">Detalles</th>
                <th style="width: 12%;">Cantidad</th>
                <th style="width: 14%;">Precio Unitario</th>
                <th style="width: 14%;">Total</th>
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
                <td class="item-details">
                    @if($item->itemable_type === 'App\\Models\\SimpleItem' && $item->itemable)
                        {{ $item->itemable->horizontal_size }}×{{ $item->itemable->vertical_size }}cm<br>
                        Tintas: {{ $item->itemable->ink_front_count }}+{{ $item->itemable->ink_back_count }}
                        @if($item->itemable->paper)
                            <br>{{ $item->itemable->paper->name }} {{ $item->itemable->paper->weight }}g
                        @endif
                    @elseif($item->itemable_type === 'App\\Models\\TalonarioItem' && $item->itemable)
                        Talonario {{ $item->itemable->prefijo ? $item->itemable->prefijo . '-' : '' }}{{ str_pad($item->itemable->numero_inicial, 3, '0', STR_PAD_LEFT) }} al {{ str_pad($item->itemable->numero_final, 3, '0', STR_PAD_LEFT) }}<br>
                        {{ $item->itemable->numeros_por_talonario }} números por talonario<br>
                        {{ $item->itemable->ancho }}×{{ $item->itemable->alto }}cm
                    @elseif($item->itemable_type === 'App\\Models\\MagazineItem' && $item->itemable)
                        Revista {{ $item->itemable->closed_width }}×{{ $item->itemable->closed_height }}cm cerrada<br>
                        Encuadernación: {{ ucfirst($item->itemable->binding_type ?? 'No definida') }}
                    @elseif($item->itemable_type === 'App\\Models\\DigitalItem' && $item->itemable)
                        Servicio digital {{ ucfirst($item->itemable->pricing_type ?? 'unit') }}<br>
                        @if($item->itemable->pricing_type === 'size' && $item->itemable->width && $item->itemable->height)
                            {{ $item->itemable->width }}×{{ $item->itemable->height }}cm
                        @endif
                    @elseif($item->itemable_type === 'App\\Models\\CustomItem' && $item->itemable)
                        Item personalizado
                        @if($item->itemable->notes)
                            <br>{{ $item->itemable->notes }}
                        @endif
                    @elseif($item->itemable_type === 'App\\Models\\Product' && $item->itemable)
                        Producto inventario<br>
                        Código: {{ $item->itemable->code }}
                    @else
                        Item estándar
                    @endif
                </td>
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
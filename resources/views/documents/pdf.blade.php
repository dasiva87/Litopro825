<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $document->number }} - {{ $document->documentType->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info {
            text-align: right;
            margin-bottom: 20px;
        }
        .document-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .contact-info {
            margin-bottom: 20px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .items-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .totals {
            margin-top: 20px;
            text-align: right;
        }
        .total-line {
            margin: 5px 0;
        }
        .final-total {
            font-weight: bold;
            font-size: 1.2em;
            border-top: 2px solid #333;
            padding-top: 10px;
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
            <p><strong>Número:</strong> {{ $document->document_number }}</p>
            <p><strong>Fecha:</strong> {{ $document->date->format('d/m/Y') }}</p>
            <p><strong>Estado:</strong> {{ ucfirst($document->status) }}</p>
        </div>
    </div>

    <div class="contact-info">
        <h3>Cliente:</h3>
        @if($document->contact)
            <p><strong>{{ $document->contact->name }}</strong></p>
            <p>{{ $document->contact->email }}</p>
            <p>{{ $document->contact->phone }}</p>
            <p>{{ $document->contact->address }}</p>
        @endif
    </div>

    @if($document->items && $document->items->count() > 0)
    <table class="items-table">
        <thead>
            <tr>
                <th>Descripción</th>
                <th>Detalles</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td>
                    @if($item->itemable_type === 'App\\Models\\SimpleItem' && $item->itemable)
                        <small>
                            {{ $item->itemable->horizontal_size }}x{{ $item->itemable->vertical_size }}cm<br>
                            Tintas: {{ $item->itemable->ink_front_count }}+{{ $item->itemable->ink_back_count }}
                            @if($item->itemable->paper)
                                <br>Papel: {{ $item->itemable->paper->name }} {{ $item->itemable->paper->weight }}g
                            @endif
                        </small>
                    @elseif($item->itemable_type === 'App\\Models\\Product' && $item->itemable)
                        <small>
                            Producto de inventario<br>
                            Código: {{ $item->itemable->code }}
                        </small>
                    @else
                        <small>Item estándar</small>
                    @endif
                </td>
                <td>{{ number_format($item->quantity) }} uds</td>
                <td>${{ number_format($item->unit_price, 2) }}</td>
                <td>${{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="total-line">
            <strong>Subtotal: ${{ number_format($document->subtotal, 2) }}</strong>
        </div>
        @if($document->tax_amount > 0)
        <div class="total-line">
            <strong>Impuestos: ${{ number_format($document->tax_amount, 2) }}</strong>
        </div>
        @endif
        @if($document->discount_amount > 0)
        <div class="total-line">
            <strong>Descuento: -${{ number_format($document->discount_amount, 2) }}</strong>
        </div>
        @endif
        <div class="total-line final-total">
            <strong>Total: ${{ number_format($document->total, 2) }}</strong>
        </div>
    </div>
    @endif

    @if($document->notes)
    <div style="margin-top: 30px;">
        <h3>Observaciones:</h3>
        <p>{{ $document->notes }}</p>
    </div>
    @endif

    <div style="margin-top: 50px; text-align: center; color: #666; font-size: 0.9em;">
        <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
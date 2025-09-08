<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $emailData['subject'] ?? $document->documentType->name }}</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-logo {
            max-height: 60px;
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }
        .document-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #495057;
        }
        .info-value {
            color: #6c757d;
        }
        .message-content {
            margin: 25px 0;
            line-height: 1.8;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
        }
        .contact-info {
            margin-top: 15px;
        }
        .highlight {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 10px;
            margin: 15px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header con información de la empresa -->
        <div class="header">
            @if($company->logo)
                <img src="{{ $company->logo_url }}" alt="{{ $company->name }}" class="company-logo">
            @endif
            <h1 class="company-name">{{ $company->name }}</h1>
        </div>

        <!-- Saludo personalizado -->
        <div class="greeting">
            <p><strong>Estimado/a {{ $contact->name ?? 'Cliente' }},</strong></p>
            
            @if(isset($emailData['custom_message']) && $emailData['custom_message'])
                <div class="message-content">
                    {!! nl2br(e($emailData['custom_message'])) !!}
                </div>
            @else
                <p>Nos complace enviarle el documento solicitado con todos los detalles de su {{ strtolower($document->documentType->name) }}.</p>
            @endif
        </div>

        <!-- Información del documento -->
        <div class="document-info">
            <div class="info-row">
                <span class="info-label">Tipo de Documento:</span>
                <span class="info-value">{{ $document->documentType->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Número:</span>
                <span class="info-value">{{ $document->document_number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha:</span>
                <span class="info-value">{{ $document->created_at->format('d/m/Y') }}</span>
            </div>
            @if($document->valid_until)
            <div class="info-row">
                <span class="info-label">Válido hasta:</span>
                <span class="info-value">{{ $document->valid_until->format('d/m/Y') }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">Total:</span>
                <span class="info-value"><strong>${{ number_format($document->total, 2) }}</strong></span>
            </div>
        </div>

        @if($document->documentType->code === 'QUOTE')
        <div class="highlight">
            <strong>📋 Información Importante:</strong>
            <br>• Esta cotización tiene una validez de {{ $document->valid_until ? $document->valid_until->diffInDays($document->created_at) : '30' }} días
            <br>• Los precios pueden estar sujetos a cambios según disponibilidad de materiales
            <br>• Para proceder con el trabajo, por favor confirme su aceptación
        </div>
        @endif

        <!-- Resumen de items (primeros 5) -->
        @if($document->items->count() > 0)
        <div class="document-info">
            <h3 style="margin-top: 0; color: #2c3e50;">Resumen de Items:</h3>
            @foreach($document->items->take(5) as $item)
            <div class="info-row">
                <span class="info-label">• {{ $item->quantity }}x</span>
                <span class="info-value">{{ $item->itemable->description ?? $item->itemable->name ?? 'Item' }}</span>
            </div>
            @endforeach
            
            @if($document->items->count() > 5)
            <div class="info-row">
                <span class="info-value" style="font-style: italic;">
                    ... y {{ $document->items->count() - 5 }} items más (ver PDF adjunto)
                </span>
            </div>
            @endif
        </div>
        @endif

        <!-- Llamada a la acción -->
        <div style="text-align: center; margin: 25px 0;">
            <p><strong>📎 Encontrará el documento completo en el archivo PDF adjunto</strong></p>
            
            @if($document->documentType->code === 'QUOTE')
                <p>Para cualquier consulta o para proceder con el pedido, no dude en contactarnos.</p>
            @endif
        </div>

        <!-- Footer con información de contacto -->
        <div class="footer">
            <div class="contact-info">
                <strong>{{ $company->name }}</strong><br>
                @if($company->phone)
                    📞 {{ $company->phone }}<br>
                @endif
                📧 {{ $company->email }}<br>
                @if($company->website)
                    🌐 {{ $company->website }}<br>
                @endif
                @if($company->address)
                    📍 {{ $company->address }}
                @endif
            </div>
            
            <p style="margin-top: 20px; font-size: 12px;">
                Este email fue generado automáticamente por el sistema LitoPro.<br>
                Si tiene alguna pregunta, por favor responda a este correo.
            </p>
        </div>
    </div>
</body>
</html>
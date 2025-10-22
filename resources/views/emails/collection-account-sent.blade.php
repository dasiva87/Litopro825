<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta de Cobro</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #1e40af; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">{{ $collectionAccount->company->name }}</h1>
        <p style="margin: 10px 0 0 0; font-size: 14px;">Cuenta de Cobro</p>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border: 1px solid #ddd; border-top: none;">
        <p style="font-size: 16px; margin-top: 0;">Estimado cliente,</p>

        <p>Le enviamos adjunta la <strong>Cuenta de Cobro #{{ $collectionAccount->account_number }}</strong> por los servicios/productos suministrados.</p>

        @if($customMessage)
            <div style="background: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;">
                <p style="margin: 0; font-size: 14px;">{{ $customMessage }}</p>
            </div>
        @endif

        <div style="background: white; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ddd;">
            <h3 style="margin-top: 0; color: #1e40af;">Detalles de la Cuenta</h3>
            <table style="width: 100%; font-size: 14px;">
                <tr>
                    <td style="padding: 5px 0;"><strong>Número:</strong></td>
                    <td style="padding: 5px 0;">{{ $collectionAccount->account_number }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>Fecha de Emisión:</strong></td>
                    <td style="padding: 5px 0;">{{ $collectionAccount->issue_date->format('d/m/Y') }}</td>
                </tr>
                @if($collectionAccount->due_date)
                <tr>
                    <td style="padding: 5px 0;"><strong>Fecha de Vencimiento:</strong></td>
                    <td style="padding: 5px 0;">{{ $collectionAccount->due_date->format('d/m/Y') }}</td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 5px 0;"><strong>Total a Pagar:</strong></td>
                    <td style="padding: 5px 0; font-size: 18px; color: #1e40af; font-weight: bold;">
                        ${{ number_format($collectionAccount->total_amount, 2) }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>Items:</strong></td>
                    <td style="padding: 5px 0;">{{ $collectionAccount->documentItems->count() }}</td>
                </tr>
            </table>
        </div>

        <p style="font-size: 14px;">Por favor, revise el documento PDF adjunto para ver los detalles completos de los items facturados.</p>

        @if($collectionAccount->company->phone || $collectionAccount->company->email)
        <div style="background: #e0e7ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #1e40af;">Información de Contacto</h4>
            @if($collectionAccount->company->phone)
                <p style="margin: 5px 0; font-size: 14px;">📞 {{ $collectionAccount->company->phone }}</p>
            @endif
            @if($collectionAccount->company->email)
                <p style="margin: 5px 0; font-size: 14px;">✉️ {{ $collectionAccount->company->email }}</p>
            @endif
            @if($collectionAccount->company->address)
                <p style="margin: 5px 0; font-size: 14px;">📍 {{ $collectionAccount->company->address }}</p>
            @endif
        </div>
        @endif

        <p style="font-size: 14px;">Agradecemos su preferencia y quedamos atentos a cualquier consulta.</p>

        <p style="font-size: 14px; margin-bottom: 0;">Cordialmente,</p>
        <p style="font-size: 16px; font-weight: bold; margin-top: 5px; color: #1e40af;">
            {{ $collectionAccount->company->name }}
        </p>
    </div>

    <div style="background: #f8f9fa; padding: 15px; text-align: center; border-radius: 0 0 5px 5px; border: 1px solid #ddd; border-top: none;">
        <p style="margin: 0; font-size: 12px; color: #666;">
            Este es un email automático generado por LitoPro.<br>
            Por favor, no responda directamente a este correo.
        </p>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Producción</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #6366f1; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">{{ $company->name }}</h1>
        <p style="margin: 10px 0 0 0; font-size: 14px;">Orden de Producción</p>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border: 1px solid #ddd; border-top: none;">
        <p style="font-size: 16px; margin-top: 0;">
            @if($supplier)
                Estimado/a {{ $supplier->name }},
            @elseif($operator)
                Estimado/a {{ $operator->name }},
            @else
                Estimado colaborador,
            @endif
        </p>

        <p>Le enviamos adjunta la <strong>Orden de Producción #{{ $order->production_number }}</strong> con los detalles del trabajo a realizar.</p>

        @if($customMessage ?? false)
            <div style="background: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;">
                <p style="margin: 0; font-size: 14px;">{{ $customMessage }}</p>
            </div>
        @endif

        <div style="background: white; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ddd;">
            <h3 style="margin-top: 0; color: #6366f1;">Detalles de la Orden</h3>
            <table style="width: 100%; font-size: 14px;">
                <tr>
                    <td style="padding: 5px 0;"><strong>Número de Orden:</strong></td>
                    <td style="padding: 5px 0;">{{ $order->production_number }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>Estado:</strong></td>
                    <td style="padding: 5px 0;">
                        <span style="background: {{ $order->status->getColor() === 'success' ? '#22c55e' : ($order->status->getColor() === 'warning' ? '#f59e0b' : '#6366f1') }}; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                            {{ $order->status->getLabel() }}
                        </span>
                    </td>
                </tr>
                @if($order->scheduled_date)
                <tr>
                    <td style="padding: 5px 0;"><strong>Fecha Programada:</strong></td>
                    <td style="padding: 5px 0;">{{ $order->scheduled_date->format('d/m/Y') }}</td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 5px 0;"><strong>Total Items:</strong></td>
                    <td style="padding: 5px 0;">{{ $order->total_items ?? 0 }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>Total Millares:</strong></td>
                    <td style="padding: 5px 0; font-size: 18px; color: #6366f1; font-weight: bold;">
                        {{ number_format($order->total_impressions ?? 0, 2) }} M
                    </td>
                </tr>
                @if($order->estimated_hours)
                <tr>
                    <td style="padding: 5px 0;"><strong>Horas Estimadas:</strong></td>
                    <td style="padding: 5px 0;">{{ number_format($order->estimated_hours, 1) }} horas</td>
                </tr>
                @endif
            </table>
        </div>

        @if($operator)
        <div style="background: #e0e7ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #6366f1;">Operador Asignado</h4>
            <p style="margin: 5px 0; font-size: 14px;">👤 {{ $operator->name }}</p>
            @if($operator->email)
                <p style="margin: 5px 0; font-size: 14px;">✉️ {{ $operator->email }}</p>
            @endif
        </div>
        @endif

        @if($supplier)
        <div style="background: #dbeafe; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #6366f1;">Proveedor</h4>
            <p style="margin: 5px 0; font-size: 14px;">🏢 {{ $supplier->name }}</p>
            @if($supplier->phone)
                <p style="margin: 5px 0; font-size: 14px;">📞 {{ $supplier->phone }}</p>
            @endif
            @if($supplier->email)
                <p style="margin: 5px 0; font-size: 14px;">✉️ {{ $supplier->email }}</p>
            @endif
        </div>
        @endif

        @if($order->notes)
        <div style="background: #fef3c7; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <h4 style="margin-top: 0; color: #92400e;">📝 Notas de Producción</h4>
            <p style="margin: 5px 0; font-size: 14px;">{{ $order->notes }}</p>
        </div>
        @endif

        <p style="font-size: 14px;">Por favor, revise el documento PDF adjunto para ver los detalles completos de los items a producir.</p>

        <div style="background: #e0e7ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #6366f1;">Información de Contacto</h4>
            @if($company->phone)
                <p style="margin: 5px 0; font-size: 14px;">📞 {{ $company->phone }}</p>
            @endif
            @if($company->email)
                <p style="margin: 5px 0; font-size: 14px;">✉️ {{ $company->email }}</p>
            @endif
            @if($company->address)
                <p style="margin: 5px 0; font-size: 14px;">📍 {{ $company->address }}</p>
            @endif
        </div>

        <p style="font-size: 14px;">Quedamos atentos a cualquier consulta o aclaración que necesite.</p>

        <p style="font-size: 14px; margin-bottom: 0;">Cordialmente,</p>
        <p style="font-size: 16px; font-weight: bold; margin-top: 5px; color: #6366f1;">
            {{ $company->name }}
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

@component('emails.layouts.company-mail', ['company' => $purchaseOrder->company])
# Nueva Orden de Pedido

Estimado {{ $purchaseOrder->supplierCompany->name ?? $purchaseOrder->supplier->name ?? 'Proveedor' }},

Han recibido una nueva orden de pedido de **{{ $purchaseOrder->company->name }}**.

@component('mail::panel')
**Número de Orden:** #{{ $purchaseOrder->order_number }}
**Fecha de Orden:** {{ $purchaseOrder->order_date->format('d/m/Y') }}
**Total:** ${{ number_format($purchaseOrder->total_amount, 2) }} COP
@if($purchaseOrder->expected_delivery_date)
**Fecha de Entrega Esperada:** {{ $purchaseOrder->expected_delivery_date->format('d/m/Y') }}
@endif
@endcomponent

## Detalles de la Orden

La orden contiene **{{ $purchaseOrder->documentItems->count() }}** items por un valor total de **${{ number_format($purchaseOrder->total_amount, 2) }} COP**.

@if($purchaseOrder->notes)
### Notas Adicionales
{{ $purchaseOrder->notes }}
@endif

**Adjunto encontrará la orden completa en formato PDF.** Por favor confirmen la recepción de esta orden y procedan según sus procesos internos.

Saludos cordiales,
**{{ $purchaseOrder->company->name }}**

---
*Esta orden fue generada automáticamente el {{ now()->format('d/m/Y H:i') }}*
@endcomponent
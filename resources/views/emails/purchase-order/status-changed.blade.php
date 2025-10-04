@component('mail::message')
# Cambio de Estado - Orden #{{ $purchaseOrder->order_number }}

Estimado equipo de {{ $purchaseOrder->company->name }},

El estado de la orden de pedido ha sido actualizado.

@component('mail::panel')
**Número de Orden:** #{{ $purchaseOrder->order_number }}
**Estado Anterior:** {{ $oldStatusLabel }}
**Estado Actual:** {{ $newStatusLabel }}
**Proveedor:** {{ $purchaseOrder->supplierCompany->name }}
**Total:** ${{ number_format($purchaseOrder->total_amount, 2) }} COP
@endcomponent

@if($newStatus === 'confirmed')
@component('mail::panel')
🎉 **¡Excelente!** El proveedor ha confirmado la orden. Procedan con el seguimiento de la entrega.
@endcomponent
@elseif($newStatus === 'received')
@component('mail::panel')
✅ **¡Orden Completada!** Todos los items han sido recibidos satisfactoriamente.
@endcomponent
@elseif($newStatus === 'cancelled')
@component('mail::panel')
❌ **Orden Cancelada** - La orden ha sido cancelada. Pueden proceder a crear una nueva orden si es necesario.
@endcomponent
@endif

@component('mail::button', ['url' => route('purchase-orders.pdf', $purchaseOrder->id)])
Ver Orden
@endcomponent

Manténganse al tanto del progreso de sus órdenes desde el panel de administración.

Saludos,
**Sistema LitoPro**

---
*Notificación generada automáticamente el {{ now()->format('d/m/Y H:i') }}*
@endcomponent
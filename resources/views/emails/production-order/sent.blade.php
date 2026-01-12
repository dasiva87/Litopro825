@component('emails.layouts.company-mail', ['company' => $productionOrder->company])
# Nueva Orden de Producción

Estimado {{ $productionOrder->operator->name ?? $productionOrder->supplierCompany->name ?? $productionOrder->supplier->name ?? 'Operador' }},

Se ha generado una nueva orden de producción de **{{ $productionOrder->company->name }}**.

@component('mail::panel')
**Número de Orden:** #{{ $productionOrder->production_number }}
**Estado:** {{ $productionOrder->status->getLabel() }}
**Fecha Programada:** {{ $productionOrder->scheduled_date ? $productionOrder->scheduled_date->format('d/m/Y') : 'No definida' }}
**Total de Items:** {{ $productionOrder->total_items }}
@if($productionOrder->total_impressions)
**Total de Impresiones:** {{ number_format($productionOrder->total_impressions, 0) }}
@endif
@if($productionOrder->estimated_hours)
**Horas Estimadas:** {{ number_format($productionOrder->estimated_hours, 1) }}
@endif
@endcomponent

## Detalles de la Orden

Esta orden contiene **{{ $productionOrder->documentItems->count() }}** item(s) para producción.

@if($productionOrder->notes)
### Notas de Producción
{{ $productionOrder->notes }}
@endif

**Adjunto encontrará el documento completo en formato PDF** con todos los detalles de producción.

Saludos cordiales,
**{{ $productionOrder->company->name }}**

---
*Esta orden fue enviada el {{ now()->format('d/m/Y H:i') }}*
@endcomponent

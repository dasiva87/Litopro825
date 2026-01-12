@component('emails.layouts.company-mail', ['company' => $document->company])
# Nueva {{ $document->documentType->name ?? 'Cotización' }}

Estimado {{ $document->clientCompany->name ?? $document->contact->name ?? 'Cliente' }},

Nos complace enviarle la {{ strtolower($document->documentType->name ?? 'cotización') }} solicitada de **{{ $document->company->name }}**.

@component('mail::panel')
**Número:** #{{ $document->document_number }}
**Fecha:** {{ $document->date->format('d/m/Y') }}
**Total:** ${{ number_format($document->total, 2) }} COP
@if($document->valid_until)
**Válida Hasta:** {{ $document->valid_until->format('d/m/Y') }}
@endif
@endcomponent

## Detalles del Documento

Este documento contiene **{{ $document->items->count() }}** item(s) por un valor total de **${{ number_format($document->total, 2) }} COP**.

@if($document->notes)
### Notas Adicionales
{{ $document->notes }}
@endif

**Adjunto encontrará el documento completo en formato PDF.** Si tiene alguna pregunta o requiere modificaciones, no dude en contactarnos.

Quedamos atentos a su respuesta.

Saludos cordiales,
**{{ $document->company->name }}**

---
*Este documento fue enviado el {{ now()->format('d/m/Y H:i') }}*
@endcomponent

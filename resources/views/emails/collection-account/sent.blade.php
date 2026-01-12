@component('emails.layouts.company-mail', ['company' => $collectionAccount->company])
# Nueva Cuenta de Cobro

¡Hola {{ $recipientName }}!

Se ha generado una nueva cuenta de cobro para su revisión.

@component('mail::panel')
**Número de Cuenta:** {{ $collectionAccount->account_number }}
**Total:** ${{ number_format($collectionAccount->total_amount, 2) }} COP
**Fecha de Emisión:** {{ $collectionAccount->issue_date->format('d/m/Y') }}
**Fecha de Vencimiento:** {{ $collectionAccount->due_date ? $collectionAccount->due_date->format('d/m/Y') : 'No definida' }}
@endcomponent

**Adjunto encontrará el documento completo en formato PDF.**

Si tiene alguna pregunta o requiere información adicional, no dude en contactarnos.

Gracias por su preferencia.

Saludos cordiales,
**{{ $collectionAccount->company->name }}**

---
*Este documento fue enviado el {{ now()->format('d/m/Y H:i') }}*
@endcomponent

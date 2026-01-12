@component('mail::layout')
{{-- Header con logo de empresa --}}
@slot('header')
@component('mail::header', ['url' => config('app.url'), 'companyLogo' => $company->getLogoUrl(), 'companyName' => $company->name ?? config('app.name')])
{{ $company->name ?? config('app.name') }}
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
© {{ date('Y') }} {{ $company->name ?? config('app.name') }}. Todos los derechos reservados.
@endcomponent
@endslot
@endcomponent

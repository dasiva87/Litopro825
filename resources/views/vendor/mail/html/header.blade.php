@props(['url', 'companyLogo' => null, 'companyName' => null])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if ($companyLogo)
{{-- Logo de la empresa emisora --}}
<img src="{{ $companyLogo }}" class="logo" alt="{{ $companyName ?? 'Logo' }}" style="height: 60px; max-width: 250px; object-fit: contain;">
@elseif ($companyName)
{{-- Nombre de la empresa si no tiene logo --}}
<span style="font-size: 24px; font-weight: bold; color: #3490dc;">{{ $companyName }}</span>
@elseif (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>

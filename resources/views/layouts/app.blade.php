<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="{{ filament()->getTheme() === 'dark' ? 'dark' : '' }}"
      dir="{{ __('filament-panels::layout.direction') ?? 'ltr' }}"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @filamentStyles
    @stack('styles')

</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">

    <!-- Contenido principal -->
    {{ $slot ?? '' }}

    @filamentScripts
    @stack('scripts')

</body>
</html>
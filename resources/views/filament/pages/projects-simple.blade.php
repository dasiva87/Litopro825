<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Proyectos - {{ config('app.name') }}</title>
    @filamentStyles
    @vite('resources/css/app.css')
    <style>
        body {
            background-color: #f3f4f6;
        }
    </style>
</head>
<body class="antialiased">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Proyectos</h1>
                <a href="{{ route('filament.admin.pages.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-700">
                    ← Volver al Dashboard
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">Lista de Proyectos</h2>
                        <p class="text-sm text-gray-600 mt-1">Selecciona un proyecto para ver sus documentos</p>
                    </div>

                    <div class="divide-y divide-gray-200">
                        @forelse($projects as $project)
                            <a href="{{ route('filament.admin.pages.project-detail', ['code' => $project['code']]) }}"
                               class="block p-6 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-4 flex-1">
                                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 flex-shrink-0">
                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                            </svg>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-lg font-semibold text-gray-900">
                                                {{ $project['code'] }}
                                            </h3>
                                            <div class="flex items-center gap-4 mt-1 text-sm text-gray-600">
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    Creado: {{ \Carbon\Carbon::parse($project['first_date'])->format('d/m/Y') }}
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    {{ $project['documents_count'] }} documento(s)
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div class="text-lg font-bold text-gray-900">
                                            ${{ number_format($project['total_amount'], 0, ',', '.') }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Última actualización: {{ \Carbon\Carbon::parse($project['last_update'])->diffForHumans() }}
                                        </div>
                                    </div>

                                    <svg class="w-6 h-6 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </a>
                        @empty
                            <div class="p-12 text-center">
                                <svg class="mx-auto w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay proyectos</h3>
                                <p class="text-gray-600">
                                    Los proyectos se crean automáticamente cuando agregas un código de referencia a una cotización.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @filamentScripts
    @vite('resources/js/app.js')
</body>
</html>

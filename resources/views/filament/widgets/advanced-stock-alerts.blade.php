@php
    $data = $this->getViewData();
@endphp

<x-filament-widgets::widget>
    <x-filament::section class="fi-section-content-ctn">
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center space-x-3">
                    <div class="flex items-center justify-center w-8 h-8 bg-blue-100 dark:bg-blue-900/20 rounded-lg shrink-0">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <span class="text-lg font-semibold text-gray-950 dark:text-white">{{ $this->getHeading() }}</span>
                </div>
                <x-filament::button
                    wire:click="refreshAlerts"
                    size="sm"
                    color="gray"
                    icon="heroicon-o-arrow-path"
                >
                    Actualizar
                </x-filament::button>
            </div>
        </x-slot>

        <div class="space-y-6">
            {{-- Resumen de Alertas - Mejorado sin conflictos --}}
            <div class="fi-widget-grid grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Alertas Críticas -->
                <div class="fi-stat-card bg-red-50 dark:bg-red-900/10 rounded-xl border border-red-200 dark:border-red-700/30 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                                <span class="text-sm font-medium text-red-700 dark:text-red-300">Críticas</span>
                            </div>
                            <div class="text-2xl font-bold text-red-900 dark:text-red-100">
                                {{ $data['activeAlerts']['critical'] }}
                            </div>
                        </div>
                        <div class="flex items-center justify-center w-8 h-8 text-red-400 dark:text-red-500 shrink-0">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Alertas Altas -->
                <div class="fi-stat-card bg-orange-50 dark:bg-orange-900/10 rounded-xl border border-orange-200 dark:border-orange-700/30 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                                <span class="text-sm font-medium text-orange-700 dark:text-orange-300">Altas</span>
                            </div>
                            <div class="text-2xl font-bold text-orange-900 dark:text-orange-100">
                                {{ $data['alertsBySeverity']['high'] }}
                            </div>
                        </div>
                        <div class="flex items-center justify-center w-8 h-8 text-orange-400 dark:text-orange-500 shrink-0">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Alertas Medias -->
                <div class="fi-stat-card bg-yellow-50 dark:bg-yellow-900/10 rounded-xl border border-yellow-200 dark:border-yellow-700/30 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                <span class="text-sm font-medium text-yellow-700 dark:text-yellow-300">Medias</span>
                            </div>
                            <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">
                                {{ $data['alertsBySeverity']['medium'] }}
                            </div>
                        </div>
                        <div class="flex items-center justify-center w-8 h-8 text-yellow-400 dark:text-yellow-500 shrink-0">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Alertas Bajas -->
                <div class="fi-stat-card bg-green-50 dark:bg-green-900/10 rounded-xl border border-green-200 dark:border-green-700/30 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span class="text-sm font-medium text-green-700 dark:text-green-300">Bajas</span>
                            </div>
                            <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                                {{ $data['alertsBySeverity']['low'] }}
                            </div>
                        </div>
                        <div class="flex items-center justify-center w-8 h-8 text-green-400 dark:text-green-500 shrink-0">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stock por Tipo - Diseño simplificado --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Productos --}}
                <div class="fi-section bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex items-center justify-center w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg shrink-0">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Productos</h3>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Total</span>
                            <span class="text-lg font-semibold text-gray-950 dark:text-white">{{ $data['stockStats']['products']['total'] }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <span class="text-sm font-medium text-green-700 dark:text-green-300">En Stock</span>
                            <span class="text-lg font-semibold text-green-700 dark:text-green-300">{{ $data['stockStats']['products']['in_stock'] }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <span class="text-sm font-medium text-yellow-700 dark:text-yellow-300">Stock Bajo</span>
                            <span class="text-lg font-semibold text-yellow-700 dark:text-yellow-300">{{ $data['stockStats']['products']['low_stock'] }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <span class="text-sm font-medium text-red-700 dark:text-red-300">Sin Stock</span>
                            <span class="text-lg font-semibold text-red-700 dark:text-red-300">{{ $data['stockStats']['products']['out_of_stock'] }}</span>
                        </div>
                    </div>
                </div>

                {{-- Papeles --}}
                <div class="fi-section bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex items-center justify-center w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg shrink-0">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Papeles</h3>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Total</span>
                            <span class="text-lg font-semibold text-gray-950 dark:text-white">{{ $data['stockStats']['papers']['total'] }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <span class="text-sm font-medium text-green-700 dark:text-green-300">En Stock</span>
                            <span class="text-lg font-semibold text-green-700 dark:text-green-300">{{ $data['stockStats']['papers']['in_stock'] }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <span class="text-sm font-medium text-yellow-700 dark:text-yellow-300">Stock Bajo</span>
                            <span class="text-lg font-semibold text-yellow-700 dark:text-yellow-300">{{ $data['stockStats']['papers']['low_stock'] }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <span class="text-sm font-medium text-red-700 dark:text-red-300">Sin Stock</span>
                            <span class="text-lg font-semibold text-red-700 dark:text-red-300">{{ $data['stockStats']['papers']['out_of_stock'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Alertas Críticas --}}
            @if(count($data['criticalAlerts']) > 0)
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Alertas Críticas Recientes</h3>
                <div class="space-y-2">
                    @foreach($data['criticalAlerts'] as $alert)
                    <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    {{ $alert['type_label'] }}
                                </span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $alert['item_name'] }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    ({{ $alert['item_type'] }})
                                </span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ $alert['message'] }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Stock: {{ $alert['current_stock'] }} | Hace {{ $alert['age_hours'] }}h
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <x-filament::button
                                wire:click="acknowledgeAlert({{ $alert['id'] }})"
                                size="sm"
                                color="gray"
                            >
                                Reconocer
                            </x-filament::button>
                            <x-filament::button
                                wire:click="resolveAlert({{ $alert['id'] }})"
                                size="sm"
                                color="success"
                            >
                                Resolver
                            </x-filament::button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Predicciones de Agotamiento --}}
            @if(count($data['depletionPredictions']['urgent']) > 0 || count($data['depletionPredictions']['critical']) > 0)
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Predicciones de Agotamiento</h3>

                @if(count($data['depletionPredictions']['urgent']) > 0)
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-red-600 mb-2">Urgente (≤3 días)</h4>
                    <div class="space-y-2">
                        @foreach($data['depletionPredictions']['urgent'] as $prediction)
                        <div class="flex items-center justify-between p-2 bg-red-50 dark:bg-red-900/20 rounded">
                            <div>
                                <span class="font-medium">{{ $prediction['item_name'] }}</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">
                                    ({{ round($prediction['days_until_depletion'], 1) }} días)
                                </span>
                            </div>
                            <span class="text-xs px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-full">
                                {{ $prediction['confidence_label'] }} confianza
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if(count($data['depletionPredictions']['critical']) > 0)
                <div>
                    <h4 class="text-sm font-medium text-orange-600 mb-2">Crítico (3-7 días)</h4>
                    <div class="space-y-2">
                        @foreach($data['depletionPredictions']['critical'] as $prediction)
                        <div class="flex items-center justify-between p-2 bg-orange-50 dark:bg-orange-900/20 rounded">
                            <div>
                                <span class="font-medium">{{ $prediction['item_name'] }}</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">
                                    ({{ round($prediction['days_until_depletion'], 1) }} días)
                                </span>
                            </div>
                            <span class="text-xs px-2 py-1 bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200 rounded-full">
                                {{ $prediction['confidence_label'] }} confianza
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif

            {{-- Valor del Inventario - Simplificado --}}
            <div class="fi-section bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="flex items-center justify-center w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg shrink-0">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Valor del Inventario</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Resumen financiero del stock</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-emerald-50 dark:bg-emerald-900/10 rounded-lg border border-emerald-200 dark:border-emerald-700/30">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-2 h-2 bg-emerald-500 rounded-full"></div>
                            <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Valor Total</span>
                        </div>
                        <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">
                            ${{ number_format($data['inventoryValue']['total_inventory_value'], 0, ',', '.') }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Productos + Papeles</p>
                    </div>

                    <div class="p-4 bg-red-50 dark:bg-red-900/10 rounded-lg border border-red-200 dark:border-red-700/30">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                                <span class="text-sm font-medium text-red-700 dark:text-red-300">En Riesgo</span>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200">
                                {{ $data['inventoryValue']['risk_percentage'] }}%
                            </span>
                        </div>
                        <div class="text-2xl font-bold text-red-700 dark:text-red-300">
                            ${{ number_format($data['inventoryValue']['low_stock_value'], 0, ',', '.') }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Stock bajo/agotado</p>
                    </div>
                </div>

                {{-- Barra de progreso del riesgo --}}
                <div class="mt-6 space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-600 dark:text-gray-400">Nivel de Riesgo</span>
                        <span class="font-semibold text-gray-950 dark:text-white">{{ $data['inventoryValue']['risk_percentage'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-gradient-to-r from-green-500 via-yellow-500 to-red-500 h-2 rounded-full transition-all duration-300"
                             style="width: {{ min(100, max(5, $data['inventoryValue']['risk_percentage'])) }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>Bajo riesgo</span>
                        <span>Alto riesgo</span>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
@php
    $results = $experiment->results ?? [];
    $control = $results['control'] ?? [];
    $variant = $results['variant'] ?? [];
    $hasResults = !empty($results) && !empty($control) && !empty($variant);
@endphp

@if ($hasResults)
    <div class="space-y-6">
        {{-- Resumen Ejecutivo --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Resumen Ejecutivo</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ number_format($results['sample_size'] ?? 0) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Participantes</div>
                </div>

                <div class="text-center">
                    <div class="text-2xl font-bold
                        @if (($results['confidence'] ?? 0) >= 95) text-green-600 dark:text-green-400
                        @elseif (($results['confidence'] ?? 0) >= 90) text-yellow-600 dark:text-yellow-400
                        @else text-red-600 dark:text-red-400 @endif">
                        {{ number_format($results['confidence'] ?? 0, 1) }}%
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Nivel de Confianza</div>
                </div>

                <div class="text-center">
                    <div class="text-2xl font-bold
                        @if (($results['conversion_lift'] ?? 0) > 0) text-green-600 dark:text-green-400
                        @elseif (($results['conversion_lift'] ?? 0) < 0) text-red-600 dark:text-red-400
                        @else text-gray-600 dark:text-gray-400 @endif">
                        {{ number_format($results['conversion_lift'] ?? 0, 2) }}%
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Lift de Conversión</div>
                </div>
            </div>

            @if (!empty($results['winner']))
                <div class="text-center p-3 rounded-lg
                    @if ($results['winner'] === 'variant') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                    @elseif ($results['winner'] === 'control') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                    @else bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 @endif">
                    <strong>
                        @if ($results['winner'] === 'variant')
                            🏆 La Variante es el Ganador
                        @elseif ($results['winner'] === 'control')
                            🏆 El Control es el Ganador
                        @else
                            ⚠️ Resultados No Concluyentes
                        @endif
                    </strong>
                </div>
            @endif
        </div>

        {{-- Comparación Detallada --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Plan Control --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h4 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100 flex items-center">
                    <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                    Plan Control: {{ $experiment->controlPlan->name }}
                </h4>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Participantes:</span>
                        <span class="font-semibold">{{ number_format($control['sample_size'] ?? 0) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Conversiones:</span>
                        <span class="font-semibold">{{ number_format($control['conversions'] ?? 0) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Tasa de Conversión:</span>
                        <span class="font-semibold text-blue-600 dark:text-blue-400">
                            {{ number_format($control['conversion_rate'] ?? 0, 2) }}%
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Revenue Total:</span>
                        <span class="font-semibold">${{ number_format($control['total_revenue'] ?? 0) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Revenue por Usuario:</span>
                        <span class="font-semibold">${{ number_format($control['revenue_per_user'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Plan Variante --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h4 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100 flex items-center">
                    <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                    Plan Variante: {{ $experiment->variantPlan->name }}
                </h4>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Participantes:</span>
                        <span class="font-semibold">{{ number_format($variant['sample_size'] ?? 0) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Conversiones:</span>
                        <span class="font-semibold">{{ number_format($variant['conversions'] ?? 0) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Tasa de Conversión:</span>
                        <span class="font-semibold text-green-600 dark:text-green-400">
                            {{ number_format($variant['conversion_rate'] ?? 0, 2) }}%
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Revenue Total:</span>
                        <span class="font-semibold">${{ number_format($variant['total_revenue'] ?? 0) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Revenue por Usuario:</span>
                        <span class="font-semibold">${{ number_format($variant['revenue_per_user'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Métricas de Mejora --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Análisis de Mejora</h4>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @php
                    $conversionLift = ($results['conversion_lift'] ?? 0);
                    $revenueLift = ($variant['total_revenue'] ?? 0) - ($control['total_revenue'] ?? 0);
                    $revenuePerUserLift = ($variant['revenue_per_user'] ?? 0) - ($control['revenue_per_user'] ?? 0);
                @endphp

                <div class="text-center p-3 rounded-lg bg-white dark:bg-gray-900">
                    <div class="text-xl font-bold
                        @if ($conversionLift > 0) text-green-600 dark:text-green-400
                        @elseif ($conversionLift < 0) text-red-600 dark:text-red-400
                        @else text-gray-600 dark:text-gray-400 @endif">
                        {{ $conversionLift > 0 ? '+' : '' }}{{ number_format($conversionLift, 2) }}%
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Mejora en Conversión</div>
                </div>

                <div class="text-center p-3 rounded-lg bg-white dark:bg-gray-900">
                    <div class="text-xl font-bold
                        @if ($revenueLift > 0) text-green-600 dark:text-green-400
                        @elseif ($revenueLift < 0) text-red-600 dark:text-red-400
                        @else text-gray-600 dark:text-gray-400 @endif">
                        {{ $revenueLift > 0 ? '+' : '' }}}${{ number_format($revenueLift) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Diferencia en Revenue</div>
                </div>

                <div class="text-center p-3 rounded-lg bg-white dark:bg-gray-900">
                    <div class="text-xl font-bold
                        @if ($revenuePerUserLift > 0) text-green-600 dark:text-green-400
                        @elseif ($revenuePerUserLift < 0) text-red-600 dark:text-red-400
                        @else text-gray-600 dark:text-gray-400 @endif">
                        {{ $revenuePerUserLift > 0 ? '+' : '' }}${{ number_format($revenuePerUserLift, 2) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Diferencia RPU</div>
                </div>
            </div>
        </div>

        {{-- Recomendaciones --}}
        <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4">
            <h4 class="text-lg font-semibold mb-2 text-blue-900 dark:text-blue-100">💡 Recomendaciones</h4>

            @if (($results['confidence'] ?? 0) >= 95)
                @if ($results['winner'] === 'variant')
                    <p class="text-blue-800 dark:text-blue-200">
                        <strong>Implementar la variante:</strong> Los resultados muestran una mejora estadísticamente significativa.
                        Se recomienda desplegar el plan variante a todos los usuarios.
                    </p>
                @elseif ($results['winner'] === 'control')
                    <p class="text-blue-800 dark:text-blue-200">
                        <strong>Mantener el control:</strong> El plan original sigue siendo superior.
                        Se recomienda no implementar la variante y explorar otras optimizaciones.
                    </p>
                @endif
            @elseif (($results['confidence'] ?? 0) >= 90)
                <p class="text-blue-800 dark:text-blue-200">
                    <strong>Extender el experimento:</strong> Los resultados son prometedores pero necesitan más datos
                    para alcanzar significancia estadística suficiente.
                </p>
            @else
                <p class="text-blue-800 dark:text-blue-200">
                    <strong>Experimento inconcluyente:</strong> Se necesita significativamente más tiempo o una muestra
                    mayor para obtener resultados confiables.
                </p>
            @endif
        </div>
    </div>
@else
    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
        <div class="text-4xl mb-2">📊</div>
        <p>Los resultados se mostrarán una vez que el experimento esté completado.</p>
        @if ($experiment->status === 'active')
            <p class="text-sm mt-1">Experimento en progreso...</p>
        @endif
    </div>
@endif
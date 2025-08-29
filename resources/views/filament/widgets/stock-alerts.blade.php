<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-danger-500" />
                <span class="font-semibold text-gray-900 dark:text-white">Stock Crítico</span>
                <x-filament::badge color="danger" size="sm">
                    {{ $totalCriticalItems }}
                </x-filament::badge>
            </div>
        </x-slot>
        
        <div class="space-y-4">
            <!-- Resumen de Stock Crítico -->
            <div class="bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 rounded-lg p-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-danger-700 dark:text-danger-300">
                        Costo Estimado de Reposición
                    </span>
                </div>
                <p class="text-lg font-bold text-danger-800 dark:text-danger-200">
                    ${{ number_format($estimatedRestockCost, 0, '.', ',') }}
                </p>
            </div>
            
            <!-- Lista de Productos con Stock Crítico -->
            @if($criticalStock->count() > 0)
                <div class="space-y-2">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Productos Críticos ({{ $criticalStock->count() }})
                    </h4>
                    
                    @foreach($criticalStock as $product)
                        <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $product['name'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $product['supplier'] }}
                                </p>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <div class="text-right">
                                    <p class="text-sm font-semibold
                                        @if($product['urgency_level'] === 'critical') text-red-600 dark:text-red-400
                                        @elseif($product['urgency_level'] === 'high') text-orange-600 dark:text-orange-400
                                        @else text-yellow-600 dark:text-yellow-400
                                        @endif
                                    ">
                                        {{ $product['current_stock'] }}/{{ $product['min_stock'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">uds</p>
                                </div>
                                
                                <x-filament::badge 
                                    :color="match($product['urgency_level']) {
                                        'critical' => 'danger',
                                        'high' => 'warning', 
                                        'medium' => 'primary',
                                        default => 'secondary'
                                    }"
                                    size="xs"
                                >
                                    @if($product['urgency_level'] === 'critical')
                                        SIN STOCK
                                    @elseif($product['urgency_level'] === 'high')
                                        URGENTE
                                    @elseif($product['urgency_level'] === 'medium')
                                        CRÍTICO
                                    @else
                                        BAJO
                                    @endif
                                </x-filament::badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            
            <!-- Lista de Productos con Stock Bajo -->
            @if($lowStock->count() > 0)
                <div class="space-y-2 mt-4">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Stock Bajo ({{ $lowStock->count() }})
                    </h4>
                    
                    @foreach($lowStock as $product)
                        <div class="flex items-center justify-between p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $product['name'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $product['supplier'] }}
                                </p>
                            </div>
                            
                            <div class="text-right">
                                <p class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">
                                    {{ $product['current_stock'] }}/{{ $product['min_stock'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">uds</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            
            <!-- Botones de Acción -->
            <div class="flex gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                <x-filament::button
                    color="danger"
                    size="sm"
                    icon="heroicon-o-shopping-cart"
                    tag="button"
                    onclick="openUrgentOrderModal()"
                    class="flex-1"
                >
                    Pedido Urgente
                </x-filament::button>
                
                <x-filament::button
                    color="info"
                    size="sm"
                    icon="heroicon-o-squares-2x2"
                    tag="a"
                    href="{{ route('filament.admin.resources.products.index') }}"
                    class="flex-1"
                >
                    Ver Inventario
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<script>
function openUrgentOrderModal() {
    // TODO: Implementar modal de pedido urgente
    const criticalProducts = @json($criticalStock);
    
    // Por ahora mostrar un alert con los productos críticos
    let message = 'Productos con stock crítico:\n\n';
    criticalProducts.forEach(product => {
        message += `• ${product.name}: ${product.current_stock}/${product.min_stock} uds\n`;
    });
    message += '\n¿Desea crear un pedido urgente para estos productos?';
    
    if (confirm(message)) {
        // Aquí se implementaría la lógica del pedido urgente
        alert('Funcionalidad de pedido urgente por implementar');
    }
}
</script>

<style>
/* Animaciones para las alertas de stock */
@keyframes pulse-danger {
    0%, 100% {
        @apply bg-danger-50 dark:bg-danger-900/20;
    }
    50% {
        @apply bg-danger-100 dark:bg-danger-900/40;
    }
}

.stock-critical-item {
    animation: pulse-danger 2s ease-in-out infinite;
}

/* Hover effects */
.stock-item:hover {
    @apply transform scale-102 shadow-md;
    transition: all 0.2s ease-in-out;
}
</style>
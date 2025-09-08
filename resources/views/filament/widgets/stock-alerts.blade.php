<!-- Stock Alerts Widget - Estilo Moderno Union Dashboard -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6" x-data="stockAlertsWidget()">
    <!-- Header con estilo moderno -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-3">
            <div class="p-2 bg-red-100 rounded-xl">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">Stock Crítico</h3>
                <p class="text-sm text-gray-500">Productos bajo mínimo</p>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                {{ $this->getTotalCriticalItems() }} productos
            </span>
            <button @click="refreshStock" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Lista de productos críticos -->
    <div class="space-y-3 mb-4">
        @foreach($this->getCriticalStockProducts() as $product)
        <div class="p-3 rounded-xl border border-gray-100 hover:border-gray-200 transition-all duration-200
                    @if($product['urgency_level'] === 'critical') bg-red-50 border-red-200 @elseif($product['urgency_level'] === 'high') bg-orange-50 border-orange-200 @else bg-yellow-50 border-yellow-200 @endif">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <h4 class="font-medium text-gray-900 text-sm">{{ $product['name'] }}</h4>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                     @if($product['urgency_level'] === 'critical') bg-red-100 text-red-700 @elseif($product['urgency_level'] === 'high') bg-orange-100 text-orange-700 @else bg-yellow-100 text-yellow-700 @endif">
                            @if($product['urgency_level'] === 'critical') CRÍTICO @elseif($product['urgency_level'] === 'high') ALTO @else MEDIO @endif
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ $product['supplier'] }}</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-sm
                            @if($product['urgency_level'] === 'critical') text-red-700 @elseif($product['urgency_level'] === 'high') text-orange-700 @else text-yellow-700 @endif">
                        {{ $product['current_stock'] }}/{{ $product['min_stock'] }}
                    </p>
                    <p class="text-xs text-gray-500">unidades</p>
                </div>
            </div>
            
            <!-- Barra de progreso -->
            <div class="mt-2">
                <div class="w-full bg-gray-200 rounded-full h-1.5">
                    @php
                        $percentage = $product['min_stock'] > 0 ? ($product['current_stock'] / $product['min_stock']) * 100 : 0;
                        $percentage = min(100, max(0, $percentage));
                    @endphp
                    <div class="h-1.5 rounded-full transition-all duration-300
                                @if($product['urgency_level'] === 'critical') bg-red-500 @elseif($product['urgency_level'] === 'high') bg-orange-500 @else bg-yellow-500 @endif" 
                         style="width: {{ $percentage }}%"></div>
                </div>
            </div>
        </div>
        @endforeach
        
        @if($this->getCriticalStockProducts()->isEmpty())
        <div class="text-center py-8">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-green-100 rounded-full mb-3">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-900">¡Excelente!</p>
            <p class="text-xs text-gray-500">No hay productos con stock crítico</p>
        </div>
        @endif
    </div>
    
    <!-- Resumen y acciones -->
    <div class="border-t border-gray-100 pt-4">
        <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
            <span>Costo estimado de reposición:</span>
            <span class="font-semibold text-gray-900">${{ number_format($this->getEstimatedRestockCost(), 0, '.', ',') }}</span>
        </div>
        
        <div class="grid grid-cols-2 gap-2">
            <button @click="createUrgentOrder" class="flex items-center justify-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                Pedido Urgente
            </button>
            <a href="{{ route('filament.admin.resources.products.index') }}" class="flex items-center justify-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition-colors">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Ver Inventario
            </a>
        </div>
    </div>
</div>

<!-- Alpine.js para funcionalidad del widget -->
<script>
function stockAlertsWidget() {
    return {
        refreshStock() {
            location.reload();
        },
        
        createUrgentOrder() {
            const criticalProducts = @json($this->getCriticalStockProducts()->toArray());
            let message = 'Productos críticos que requieren reposición:\n\n';
            
            criticalProducts.forEach(product => {
                message += `• ${product.name}: ${product.current_stock}/${product.min_stock} unidades\n`;
                message += `  Proveedor: ${product.supplier}\n`;
                message += `  Precio: $${product.last_purchase_price || 'N/A'}\n\n`;
            });
            
            message += `Costo estimado total: ${{ number_format($this->getEstimatedRestockCost(), 0, '.', ',') }}`;
            
            alert(message);
        }
    }
}
</script>
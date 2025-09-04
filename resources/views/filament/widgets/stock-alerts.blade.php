<div class="stock-alerts-widget">
    <x-filament-widgets::widget>
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <span class="font-semibold text-gray-900 dark:text-white">📋 Stock Crítico de Papel</span>
                    <x-filament::badge color="danger" size="sm">
                        Stock crítico
                    </x-filament::badge>
                </div>
            </x-slot>
            
            <div class="space-y-3">
                <!-- Producto Crítico 1 -->
                <div class="p-3 bg-red-50 rounded-lg border border-red-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-red-900">Bond Blanco 75g</p>
                            <p class="text-xs text-red-700">70x100cm</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-red-900">15/50</p>
                            <p class="text-xs text-red-700">pliegos</p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-red-200 rounded-full h-2">
                            <div class="bg-red-600 h-2 rounded-full" style="width: 30%"></div>
                        </div>
                    </div>
                    <button class="mt-2 w-full bg-red-600 hover:bg-red-700 text-white text-xs font-medium py-1.5 px-3 rounded transition-colors">
                        Solicitar más stock
                    </button>
                </div>
                
                <!-- Producto Crítico 2 -->
                <div class="p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-yellow-900">Couche 150g</p>
                            <p class="text-xs text-yellow-700">70x100cm</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-yellow-900">45/150</p>
                            <p class="text-xs text-yellow-700">pliegos</p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-yellow-200 rounded-full h-2">
                            <div class="bg-yellow-600 h-2 rounded-full" style="width: 30%"></div>
                        </div>
                    </div>
                    <button class="mt-2 w-full bg-yellow-600 hover:bg-yellow-700 text-white text-xs font-medium py-1.5 px-3 rounded transition-colors">
                        Ver en marketplace
                    </button>
                </div>
                
                <!-- Acciones -->
                <div class="flex gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <x-filament::button
                        color="danger"
                        size="sm"
                        tag="button"
                        onclick="openUrgentOrderModal()"
                        class="flex-1"
                    >
                        Pedido Urgente
                    </x-filament::button>
                    
                    <x-filament::button
                        color="info"
                        size="sm"
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
        alert('Funcionalidad de pedido urgente: Bond Blanco 75g (15/50) y Couche 150g (45/150) necesitan reposición');
    }
    </script>

    <style>
    /* Animaciones para las alertas de stock */
    @keyframes pulse-danger {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.8;
        }
    }

    .stock-critical-item {
        animation: pulse-danger 2s ease-in-out infinite;
    }

    /* Hover effects */
    .stock-item:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease-in-out;
    }
    </style>
</div>
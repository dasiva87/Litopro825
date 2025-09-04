<x-filament-widgets::widget>
    <x-filament::section>
        <div class="widget-container">
            <div class="widget-header">
                <h3 class="widget-title">⚡ Acciones Rápidas</h3>
            </div>
            
            <div class="quick-actions-grid">
                <!-- Nueva Cotización -->
                <a href="{{ route('filament.admin.resources.documents.create-quotation') }}" 
                   class="flex flex-col items-center justify-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors card-hover group">
                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-blue-900">Nueva Cotización</span>
                    <span class="text-xs text-blue-600">Crear cotización para cliente</span>
                </a>
                
                <!-- Nuevo Cliente -->
                <a href="{{ route('filament.admin.resources.contacts.create', ['type' => 'customer']) }}" 
                   class="flex flex-col items-center justify-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors card-hover group">
                    <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-green-900">Nuevo Cliente</span>
                    <span class="text-xs text-green-600">Registrar cliente nuevo</span>
                </a>
                
                <!-- Pedir Papel -->
                <button onclick="openPaperOrderModal()" 
                        class="flex flex-col items-center justify-center p-4 bg-orange-50 hover:bg-orange-100 rounded-lg transition-colors card-hover group">
                    <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-orange-900">Pedir Papel</span>
                    <span class="text-xs text-orange-600">Nuevo pedido urgente</span>
                </button>
                
                <!-- Ver Producción -->
                <a href="{{ route('filament.admin.resources.documents.index', ['tableFilters[status][value]' => 'in_production']) }}" 
                   class="flex flex-col items-center justify-center p-4 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors card-hover group">
                    <div class="w-12 h-12 bg-yellow-600 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-yellow-900">Ver Producción</span>
                    <span class="text-xs text-yellow-600">Estado del proceso</span>
                </a>
            </div>
        </div>
    </x-filament::section>

    <script>
        function openPaperOrderModal() {
            // Implementar modal para pedido de papel
            alert('Funcionalidad de pedido de papel en desarrollo');
        }
    </script>
</x-filament-widgets::widget>
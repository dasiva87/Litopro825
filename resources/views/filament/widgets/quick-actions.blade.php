<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-500/10">
                    <x-filament::icon icon="heroicon-o-bolt" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Acciones Rápidas</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Accesos directos frecuentes</p>
                </div>
            </div>
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 mt-4">
            @foreach($actions as $action)
                <div class="flex flex-col">
                    {{ $action }}
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Agregar efectos de hover personalizados
    const quickActionButtons = document.querySelectorAll('.fi-wi-quick-actions .fi-btn');
    
    quickActionButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Escuchar eventos personalizados para modals
    window.addEventListener('production-modal-opened', function() {
        // Aquí se puede implementar la lógica del modal de producción
        alert('Modal de Registro de Producción - Por implementar');
    });
    
    window.addEventListener('schedule-modal-opened', function() {
        alert('Modal de Programación - Por implementar');
    });
    
    window.addEventListener('production-report-opened', function() {
        alert('Reporte de Producción - Por implementar');
    });
    
    window.addEventListener('urgent-paper-order', function() {
        alert('Pedido Urgente de Papel - Por implementar');
    });
    
    window.addEventListener('marketplace-opened', function() {
        alert('Marketplace - Por implementar');
    });
});
</script>
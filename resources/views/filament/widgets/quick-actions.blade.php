<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-bolt class="h-5 w-5 text-primary-500" />
                <span class="font-semibold text-gray-900 dark:text-white">Acciones Rápidas</span>
            </div>
        </x-slot>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($actions as $action)
                <div class="flex flex-col">
                    {{ $action }}
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<style>
/* Estilos personalizados para las acciones rápidas */
.fi-wi-quick-actions .fi-btn-group {
    @apply w-full justify-center;
}

.fi-wi-quick-actions .fi-btn {
    @apply transition-all duration-200 hover:scale-105 hover:shadow-lg;
}

.fi-wi-quick-actions .fi-btn-group-item:hover {
    @apply transform scale-105;
}

/* Estilo especial para la calculadora */
.fi-wi-quick-actions .fi-btn[data-name="paper_calculator"] {
    @apply bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700;
    @apply shadow-xl hover:shadow-2xl;
    @apply border-2 border-primary-300;
}
</style>

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
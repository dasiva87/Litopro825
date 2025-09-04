<div class="deadlines-widget">
    <x-filament-widgets::widget>
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4M3 21h18l-2-9H5l-2 9z"/>
                    </svg>
                    <span class="font-semibold text-gray-900 dark:text-white">📅 Próximos Vencimientos</span>
                    <x-filament::badge color="warning" size="sm">
                        3
                    </x-filament::badge>
                </div>
            </x-slot>
            
            <div class="space-y-3">
                <!-- Deadline Crítico -->
                <div class="p-3 bg-orange-50 rounded-lg border border-orange-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-orange-900">Volantes Restaurante</p>
                            <p class="text-xs text-orange-700">COT-2024-089</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-orange-900">Mañana</p>
                            <p class="text-xs text-orange-700">28 Jun</p>
                        </div>
                    </div>
                </div>
                
                <!-- Deadline Hoy -->
                <div class="p-3 bg-red-50 rounded-lg border border-red-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-red-900">Catálogo Fashion</p>
                            <p class="text-xs text-red-700">ORD-2024-045</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-red-900">Hoy</p>
                            <p class="text-xs text-red-700">28 Jun</p>
                        </div>
                    </div>
                </div>
                
                <!-- Deadline En 2 días -->
                <div class="p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-yellow-900">Folletos Clínica</p>
                            <p class="text-xs text-yellow-700">ORD-2024-044</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-yellow-900">29 Jun</p>
                            <p class="text-xs text-yellow-700">En 2 días</p>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de Acción -->
                <div class="flex gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <x-filament::button
                        color="warning"
                        size="sm"
                        tag="button"
                        onclick="createDeadline()"
                        class="flex-1"
                    >
                        Nuevo Deadline
                    </x-filament::button>
                    
                    <x-filament::button
                        color="info" 
                        size="sm"
                        tag="button"
                        onclick="viewCalendar()"
                        class="flex-1"
                    >
                        Ver Calendario
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    </x-filament-widgets::widget>

    <style>
    /* Animaciones para deadlines urgentes */
    @keyframes deadline-pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.02);
        }
    }

    .deadline-overdue {
        animation: deadline-pulse 2s ease-in-out infinite;
    }

    /* Hover effects */
    .deadline-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease-in-out;
    }
    </style>

    <script>
    function createDeadline() {
        alert('Crear nuevo deadline: Funcionalidad por implementar');
    }
    
    function viewCalendar() {
        alert('Ver calendario: Funcionalidad por implementar');
    }
    
    // Auto-refresh deadlines cada 5 minutos
    setInterval(() => {
        if (typeof Livewire !== 'undefined') {
            Livewire.emit('refreshDeadlines');
        }
    }, 300000);
    </script>
</div>
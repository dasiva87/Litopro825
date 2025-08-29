<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-clock class="h-5 w-5 text-warning-500" />
                <span class="font-semibold text-gray-900 dark:text-white">Próximos Vencimientos</span>
                @if($stats['overdue'] > 0)
                    <x-filament::badge color="danger" size="sm">
                        {{ $stats['overdue'] }} Vencidos
                    </x-filament::badge>
                @endif
            </div>
        </x-slot>
        
        <div class="space-y-4">
            <!-- Estadísticas Rápidas -->
            <div class="grid grid-cols-3 gap-2">
                <div class="bg-info-50 dark:bg-info-900/20 border border-info-200 dark:border-info-800 rounded-lg p-2 text-center">
                    <p class="text-lg font-bold text-info-700 dark:text-info-300">{{ $stats['upcoming'] }}</p>
                    <p class="text-xs text-info-600 dark:text-info-400">Próximos</p>
                </div>
                
                <div class="bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 rounded-lg p-2 text-center">
                    <p class="text-lg font-bold text-danger-700 dark:text-danger-300">{{ $stats['overdue'] }}</p>
                    <p class="text-xs text-danger-600 dark:text-danger-400">Vencidos</p>
                </div>
                
                <div class="bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 rounded-lg p-2 text-center">
                    <p class="text-lg font-bold text-warning-700 dark:text-warning-300">{{ $stats['urgent'] }}</p>
                    <p class="text-xs text-warning-600 dark:text-warning-400">Urgentes</p>
                </div>
            </div>
            
            <!-- Vencimientos Atrasados -->
            @if($overdueDeadlines->count() > 0)
                <div class="space-y-2">
                    <h4 class="text-sm font-medium text-danger-700 dark:text-danger-300 flex items-center gap-1">
                        <x-heroicon-o-exclamation-triangle class="h-4 w-4" />
                        Vencidos ({{ $overdueDeadlines->count() }})
                    </h4>
                    
                    @foreach($overdueDeadlines as $deadline)
                        <div class="flex items-start gap-3 p-3 bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 rounded-lg">
                            <div class="flex-shrink-0 mt-0.5">
                                <div class="w-2 h-2 bg-danger-500 rounded-full animate-pulse"></div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-medium text-danger-800 dark:text-danger-200 truncate">
                                        {{ $deadline['title'] }}
                                    </p>
                                    <x-filament::badge color="danger" size="xs">
                                        {{ abs($deadline['days_until']) }} días
                                    </x-filament::badge>
                                </div>
                                
                                @if($deadline['description'])
                                    <p class="text-xs text-danger-600 dark:text-danger-400 mt-1 truncate">
                                        {{ $deadline['description'] }}
                                    </p>
                                @endif
                                
                                <div class="flex items-center gap-2 mt-1">
                                    <x-filament::badge color="secondary" size="xs">
                                        {{ $deadline['type_label'] }}
                                    </x-filament::badge>
                                    
                                    <span class="text-xs text-danger-500 dark:text-danger-400">
                                        {{ \Carbon\Carbon::parse($deadline['deadline_date'])->format('d/m/Y') }}
                                    </span>
                                </div>
                            </div>
                            
                            @if($deadline['url'] !== '#')
                                <a href="{{ $deadline['url'] }}" 
                                   class="flex-shrink-0 text-danger-600 hover:text-danger-800 dark:text-danger-400 dark:hover:text-danger-200">
                                    <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
            
            <!-- Próximos Vencimientos -->
            @if($upcomingDeadlines->count() > 0)
                <div class="space-y-2">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                        <x-heroicon-o-calendar-days class="h-4 w-4" />
                        Próximos ({{ $upcomingDeadlines->count() }})
                    </h4>
                    
                    @foreach($upcomingDeadlines->take(8) as $deadline)
                        <div class="flex items-start gap-3 p-3 
                            @if($deadline['priority'] === 'urgent') 
                                bg-danger-50 dark:bg-danger-900/20 border-danger-200 dark:border-danger-800
                            @elseif($deadline['priority'] === 'high') 
                                bg-warning-50 dark:bg-warning-900/20 border-warning-200 dark:border-warning-800
                            @else 
                                bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700
                            @endif
                            border rounded-lg hover:shadow-sm transition-shadow
                        ">
                            <div class="flex-shrink-0 mt-0.5">
                                <div class="w-2 h-2 rounded-full
                                    @if($deadline['priority'] === 'urgent') bg-danger-500
                                    @elseif($deadline['priority'] === 'high') bg-warning-500
                                    @else bg-info-500
                                    @endif
                                "></div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $deadline['title'] }}
                                    </p>
                                    
                                    @if($deadline['days_until'] <= 1)
                                        <x-filament::badge color="danger" size="xs">
                                            @if($deadline['days_until'] == 0) HOY @else {{ $deadline['days_until'] }}d @endif
                                        </x-filament::badge>
                                    @elseif($deadline['days_until'] <= 3)
                                        <x-filament::badge color="warning" size="xs">
                                            {{ $deadline['days_until'] }}d
                                        </x-filament::badge>
                                    @else
                                        <x-filament::badge color="info" size="xs">
                                            {{ $deadline['days_until'] }}d
                                        </x-filament::badge>
                                    @endif
                                </div>
                                
                                @if($deadline['description'])
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 truncate">
                                        {{ $deadline['description'] }}
                                    </p>
                                @endif
                                
                                <div class="flex items-center gap-2 mt-1">
                                    <x-filament::badge color="secondary" size="xs">
                                        {{ $deadline['type_label'] }}
                                    </x-filament::badge>
                                    
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($deadline['deadline_date'])->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                            </div>
                            
                            @if($deadline['url'] !== '#')
                                <a href="{{ $deadline['url'] }}" 
                                   class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                                    <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
            
            <!-- Mensaje si no hay vencimientos -->
            @if($upcomingDeadlines->count() === 0 && $overdueDeadlines->count() === 0)
                <div class="text-center py-6">
                    <x-heroicon-o-check-circle class="h-12 w-12 text-success-400 mx-auto mb-2" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        ¡Genial! No tienes vencimientos pendientes
                    </p>
                </div>
            @endif
            
            <!-- Botones de Acción -->
            <div class="flex gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                <x-filament::button
                    color="primary"
                    size="sm"
                    icon="heroicon-o-plus"
                    tag="button"
                    onclick="openNewDeadlineModal()"
                    class="flex-1"
                >
                    Nuevo Vencimiento
                </x-filament::button>
                
                <x-filament::button
                    color="secondary"
                    size="sm"
                    icon="heroicon-o-calendar-days"
                    tag="button"
                    onclick="openCalendarView()"
                    class="flex-1"
                >
                    Ver Calendario
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<script>
function openNewDeadlineModal() {
    // TODO: Implementar modal para crear nuevo vencimiento
    alert('Modal de Nuevo Vencimiento - Por implementar');
}

function openCalendarView() {
    // TODO: Implementar vista de calendario
    alert('Vista de Calendario - Por implementar');
}

// Auto-refresh cada 60 segundos para mantener actualizados los vencimientos
setInterval(function() {
    if (typeof Livewire !== 'undefined') {
        Livewire.dispatch('$refresh');
    }
}, 60000);
</script>

<style>
/* Animaciones para los elementos urgentes */
@keyframes urgent-pulse {
    0%, 100% {
        @apply opacity-100;
    }
    50% {
        @apply opacity-75;
    }
}

.deadline-urgent {
    animation: urgent-pulse 2s ease-in-out infinite;
}

/* Hover effects */
.deadline-item:hover {
    @apply transform scale-101 shadow-md;
    transition: all 0.2s ease-in-out;
}
</style>
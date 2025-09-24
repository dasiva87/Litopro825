<x-filament-panels::page>
    <div class="space-y-6">
        {{-- KPIs Mejorados --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            {{-- Entradas --}}
            <x-filament::card class="relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="p-2 bg-green-100 dark:bg-green-900/20 rounded-lg">
                                @svg('heroicon-s-arrow-trending-up', 'w-6 h-6 text-green-600')
                            </div>
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Entradas</h3>
                        </div>
                        <div class="text-3xl font-bold text-green-600">
                            {{ number_format(\App\Models\StockMovement::where('company_id', auth()->user()->company_id)->where('type', 'in')->count()) }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total de ingresos</p>
                    </div>
                    <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-green-400/10 to-green-600/5 rounded-bl-3xl"></div>
                </div>
            </x-filament::card>

            {{-- Salidas --}}
            <x-filament::card class="relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="p-2 bg-red-100 dark:bg-red-900/20 rounded-lg">
                                @svg('heroicon-s-arrow-trending-down', 'w-6 h-6 text-red-600')
                            </div>
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Salidas</h3>
                        </div>
                        <div class="text-3xl font-bold text-red-600">
                            {{ number_format(\App\Models\StockMovement::where('company_id', auth()->user()->company_id)->where('type', 'out')->count()) }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total de egresos</p>
                    </div>
                    <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-red-400/10 to-red-600/5 rounded-bl-3xl"></div>
                </div>
            </x-filament::card>

            {{-- Ajustes --}}
            <x-filament::card class="relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="p-2 bg-yellow-100 dark:bg-yellow-900/20 rounded-lg">
                                @svg('heroicon-s-adjustments-horizontal', 'w-6 h-6 text-yellow-600')
                            </div>
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Ajustes</h3>
                        </div>
                        <div class="text-3xl font-bold text-yellow-600">
                            {{ number_format(\App\Models\StockMovement::where('company_id', auth()->user()->company_id)->where('type', 'adjustment')->count()) }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Correcciones realizadas</p>
                    </div>
                    <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-yellow-400/10 to-yellow-600/5 rounded-bl-3xl"></div>
                </div>
            </x-filament::card>

            {{-- Movimientos Hoy --}}
            <x-filament::card class="relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="p-2 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                                @svg('heroicon-s-clock', 'w-6 h-6 text-blue-600')
                            </div>
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Hoy</h3>
                        </div>
                        <div class="text-3xl font-bold text-blue-600">
                            {{ number_format(\App\Models\StockMovement::where('company_id', auth()->user()->company_id)->where('created_at', '>=', now()->startOfDay())->count()) }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Movimientos del día</p>
                    </div>
                    <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-blue-400/10 to-blue-600/5 rounded-bl-3xl"></div>
                </div>
            </x-filament::card>
        </div>

        {{-- Tabla Principal --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>
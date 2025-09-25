<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>{{ $this->getHeading() }}</span>
                <x-filament::button
                    wire:click="refreshAlerts"
                    size="sm"
                    color="gray"
                    icon="heroicon-o-arrow-path"
                >
                    Actualizar
                </x-filament::button>
            </div>
        </x-slot>

        @php $data = $this->getViewData(); @endphp

        {{-- Resumen de Alertas usando componentes nativos --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-filament::card>
                <div class="text-center">
                    <div class="text-2xl font-bold">{{ $data['activeAlerts']['critical'] }}</div>
                    <div class="text-sm">Críticas</div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-center">
                    <div class="text-2xl font-bold">{{ $data['activeAlerts']['high'] }}</div>
                    <div class="text-sm">Altas</div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-center">
                    <div class="text-2xl font-bold">{{ $data['activeAlerts']['medium'] }}</div>
                    <div class="text-sm">Medias</div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-center">
                    <div class="text-2xl font-bold">{{ $data['activeAlerts']['low'] }}</div>
                    <div class="text-sm">Bajas</div>
                </div>
            </x-filament::card>
        </div>

        {{-- Estadísticas por Tipo --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <x-filament::card>
                <x-slot name="header">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-squares-2x2 class="w-5 h-5" />
                        <span class="font-medium">Productos</span>
                    </div>
                </x-slot>

                <div class="space-y-2">
                    <div>Total {{ $data['stockStats']['products']['total'] }}</div>
                    <div>En Stock {{ $data['stockStats']['products']['in_stock'] }}</div>
                    <div>Stock Bajo {{ $data['stockStats']['products']['low_stock'] }}</div>
                    <div>Sin Stock {{ $data['stockStats']['products']['out_of_stock'] }}</div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <x-slot name="header">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document class="w-5 h-5" />
                        <span class="font-medium">Papeles</span>
                    </div>
                </x-slot>

                <div class="space-y-2">
                    <div>Total {{ $data['stockStats']['papers']['total'] }}</div>
                    <div>En Stock {{ $data['stockStats']['papers']['in_stock'] }}</div>
                    <div>Stock Bajo {{ $data['stockStats']['papers']['low_stock'] }}</div>
                    <div>Sin Stock {{ $data['stockStats']['papers']['out_of_stock'] }}</div>
                </div>
            </x-filament::card>
        </div>

        {{-- Alertas Críticas Recientes --}}
        @if(count($data['criticalAlerts']) > 0)
            <x-filament::card>
                <x-slot name="header">
                    <span class="font-medium">Alertas Críticas Recientes</span>
                </x-slot>

                <div class="space-y-4">
                    @foreach($data['criticalAlerts'] as $alert)
                        <div class="border-l-4 border-l-danger-600 pl-4">
                            <div class="font-medium">{{ $alert['type_label'] }} {{ $alert['item_name'] }} ({{ $alert['item_type'] }})</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ $alert['message'] }}</div>
                            <div class="text-xs text-gray-500">Stock: {{ $alert['current_stock'] }} | {{ $alert['triggered_at'] }}</div>
                            <div class="flex gap-2 mt-2">
                                <x-filament::button
                                    size="xs"
                                    wire:click="acknowledgeAlert({{ $alert['id'] }})"
                                >
                                    Reconocer
                                </x-filament::button>
                                <x-filament::button
                                    size="xs"
                                    color="success"
                                    wire:click="resolveAlert({{ $alert['id'] }})"
                                >
                                    Resolver
                                </x-filament::button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::card>
        @endif

        {{-- Valor del Inventario --}}
        <x-filament::card>
            <x-slot name="header">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-currency-dollar class="w-5 h-5" />
                    <span class="font-medium">Valor del Inventario</span>
                </div>
            </x-slot>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="text-lg font-bold">${{ number_format($data['inventoryValue']['total_inventory_value']) }}</div>
                    <div class="text-sm">Valor Total</div>
                    <div class="text-xs">Productos + Papeles</div>
                </div>
                <div>
                    <div class="text-lg font-bold">{{ $data['inventoryValue']['risk_percentage'] }}%</div>
                    <div class="text-sm">En Riesgo</div>
                    <div class="text-xs">${{ number_format($data['inventoryValue']['low_stock_value']) }}</div>
                </div>
            </div>
        </x-filament::card>

    </x-filament::section>
</x-filament-widgets::widget>
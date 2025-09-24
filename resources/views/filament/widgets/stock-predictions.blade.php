<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Predicciones de Restock
        </x-slot>

        @if(count($this->getViewData()['predictions']['urgent']) > 0)
            <x-filament::card>
                <x-slot name="header">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-exclamation-triangle', 'w-5 h-5 text-red-600')
                        <span class="font-medium">Urgente (próximos 7 días)</span>
                    </div>
                </x-slot>

                <div class="space-y-2">
                    @foreach($this->getViewData()['predictions']['urgent'] as $item)
                        <div class="flex justify-between items-center">
                            <span>{{ $item['name'] ?? $item['item_name'] ?? 'Item desconocido' }}</span>
                            <span class="text-sm text-red-600 font-medium">{{ $item['days_until_depletion'] ?? 'N/A' }} días</span>
                        </div>
                    @endforeach
                </div>
            </x-filament::card>
        @endif

        @if(count($this->getViewData()['predictions']['critical']) > 0)
            <x-filament::card>
                <x-slot name="header">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-exclamation-circle', 'w-5 h-5 text-yellow-600')
                        <span class="font-medium">Crítico (próximos 14 días)</span>
                    </div>
                </x-slot>

                <div class="space-y-2">
                    @foreach($this->getViewData()['predictions']['critical'] as $item)
                        <div class="flex justify-between items-center">
                            <span>{{ $item['name'] }}</span>
                            <span class="text-sm text-yellow-600 font-medium">{{ $item['days_until_depletion'] }} días</span>
                        </div>
                    @endforeach
                </div>
            </x-filament::card>
        @endif

        @if(count($this->getViewData()['predictions']['urgent']) == 0 && count($this->getViewData()['predictions']['critical']) == 0)
            <div class="text-center py-8">
                @svg('heroicon-o-check-circle', 'w-5 h-5 mx-auto mb-2')
                <p>No hay alertas de restock urgentes</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
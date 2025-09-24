<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Seguimiento de Niveles de Stock
        </x-slot>

        @if(count($this->getViewData()['stock_items']) > 0)
            <div class="space-y-3">
                @foreach($this->getViewData()['stock_items'] as $item)
                    <x-filament::card>
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                @if($item['status'] === 'critical')
                                    @svg('heroicon-o-exclamation-triangle', 'w-5 h-5 text-red-600')
                                @elseif($item['status'] === 'warning')
                                    @svg('heroicon-o-exclamation-circle', 'w-5 h-5 text-yellow-600')
                                @elseif($item['status'] === 'low')
                                    @svg('heroicon-o-information-circle', 'w-5 h-5 text-orange-600')
                                @else
                                    @svg('heroicon-o-check-circle', 'w-5 h-5 text-green-600')
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium">{{ $item['name'] }}</div>
                                <div class="mt-1">
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="h-2 rounded-full {{ $item['status'] === 'critical' ? 'bg-red-600' : ($item['status'] === 'warning' ? 'bg-yellow-600' : ($item['status'] === 'low' ? 'bg-orange-600' : 'bg-green-600')) }}"
                                             style="width: {{ $item['percentage'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold {{ $item['status'] === 'critical' ? 'text-red-600' : ($item['status'] === 'warning' ? 'text-yellow-600' : ($item['status'] === 'low' ? 'text-orange-600' : 'text-green-600')) }}">
                                    {{ $item['current_stock'] }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Mín: {{ $item['min_stock'] }}</div>
                            </div>
                        </div>
                    </x-filament::card>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                @svg('heroicon-o-check-circle', 'w-5 h-5 mx-auto text-green-500 mb-2')
                <p class="text-sm text-green-600 dark:text-green-400">Todos los niveles de stock están normales</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
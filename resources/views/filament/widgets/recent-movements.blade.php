<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Movimientos Recientes
        </x-slot>

        @if(count($this->getViewData()['movements']) > 0)
            <div class="space-y-3">
                @foreach($this->getViewData()['movements'] as $movement)
                    <x-filament::card>
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                @if($movement['type'] === 'in')
                                    @svg('heroicon-m-arrow-up', 'w-5 h-5 text-green-600')
                                @else
                                    @svg('heroicon-m-arrow-down', 'w-5 h-5 text-red-600')
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium">{{ $movement['item_name'] }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ $movement['item_type'] }} • {{ $movement['created_at'] }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold {{ $movement['type'] === 'in' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $movement['type'] === 'in' ? '+' : '-' }}{{ $movement['quantity'] }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ $movement['reason'] }}</div>
                            </div>
                        </div>
                    </x-filament::card>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                @svg('heroicon-o-clipboard-document-list', 'w-5 h-5 mx-auto text-gray-400 mb-2')
                <p class="text-sm text-gray-500 dark:text-gray-400">No hay movimientos recientes</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
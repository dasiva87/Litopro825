<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Acciones Rápidas
        </x-slot>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($this->getViewData()['actions'] as $action)
                <x-filament::card class="hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center space-x-3 p-2">
                        <div class="flex-shrink-0">
                            <div class="p-2 rounded-lg {{ $action->getColor() === 'primary' ? 'bg-blue-100 dark:bg-blue-900/20' : ($action->getColor() === 'success' ? 'bg-green-100 dark:bg-green-900/20' : ($action->getColor() === 'info' ? 'bg-gray-100 dark:bg-gray-900/20' : 'bg-red-100 dark:bg-red-900/20')) }}">
                                @svg($action->getIcon(), 'w-5 h-5 ' . match($action->getColor()) {
                                    'primary' => 'text-blue-600',
                                    'success' => 'text-green-600',
                                    'info' => 'text-gray-600',
                                    'danger' => 'text-red-600',
                                    default => 'text-gray-600'
                                })
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ $action->getUrl() ?: '#' }}"
                               class="font-medium text-sm hover:underline {{ $action->getColor() === 'primary' ? 'text-blue-600 hover:text-blue-800' : ($action->getColor() === 'success' ? 'text-green-600 hover:text-green-800' : ($action->getColor() === 'info' ? 'text-gray-600 hover:text-gray-800' : 'text-red-600 hover:text-red-800')) }}"
                               @if(!$action->getUrl())
                                   wire:click="mountAction('{{ $action->getName() }}')"
                               @endif>
                                {{ $action->getLabel() }}
                            </a>
                        </div>
                    </div>
                </x-filament::card>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Acciones Rápidas
        </x-slot>

        <div class="space-y-2">
            @foreach($this->getViewData()['actions'] as $action)
                <x-filament::card class="p-3">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            @if($action->getUrl())
                                <x-filament::button
                                    :href="$action->getUrl()"
                                    :color="$action->getColor()"
                                    size="sm"
                                    :icon="$action->getIcon()"
                                >
                                    {{ $action->getLabel() }}
                                </x-filament::button>
                            @else
                                <x-filament::button
                                    :color="$action->getColor()"
                                    size="sm"
                                    :icon="$action->getIcon()"
                                    wire:click="mountAction('{{ $action->getName() }}')"
                                >
                                    {{ $action->getLabel() }}
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                </x-filament::card>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
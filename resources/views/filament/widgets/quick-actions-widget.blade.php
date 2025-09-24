<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Acciones Rápidas
        </x-slot>

        <div class="grid grid-cols-2 gap-4">
            @foreach($this->getActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
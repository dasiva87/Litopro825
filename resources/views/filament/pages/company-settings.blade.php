<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="fi-ac fi-ac-gap-3 fi-ac-wrap">
            @foreach($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Acciones Rápidas
        </x-slot>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="flex w-full">
                {{ ($this->stockEntryAction)(['size' => 'lg']) }}
            </div>
            <div class="flex w-full">
                {{ ($this->viewCriticalAction)(['size' => 'lg']) }}
            </div>
            <div class="flex w-full">
                {{ ($this->generatePurchaseOrderAction)(['size' => 'lg']) }}
            </div>
            <div class="flex w-full">
                {{ ($this->downloadReportAction)(['size' => 'lg']) }}
            </div>
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>

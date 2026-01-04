<x-filament-panels::page>
    <x-filament-widgets::widgets
        :widgets="$this->getHeaderWidgets()"
        :columns="[
            'md' => 2,
            'xl' => 3,
        ]"
    />
</x-filament-panels::page>

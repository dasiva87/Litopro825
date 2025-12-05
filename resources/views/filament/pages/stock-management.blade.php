<x-filament-panels::page>
    {{-- Header Widgets (KPIs) will be rendered automatically via getHeaderWidgets() --}}

    {{-- Quick Actions Widget (keep this one manually loaded) --}}
    <div class="mb-6">
        @livewire(\App\Filament\Widgets\QuickActionsWidget::class)
    </div>

    {{-- Footer Widgets (Trends, Tables, Movements) will be rendered automatically via getFooterWidgets() --}}
</x-filament-panels::page>

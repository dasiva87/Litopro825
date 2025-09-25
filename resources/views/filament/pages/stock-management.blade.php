<x-filament-panels::page>
    <div class="space-y-6">
        {{-- KPIs Stats --}}
        @livewire(\App\Filament\Widgets\SimpleStockKpisWidget::class)

        {{-- Actions Widget --}}
        @livewire(\App\Filament\Widgets\QuickActionsWidget::class)
    </div>
</x-filament-panels::page>
<x-filament-panels::page>
    {{-- Header Widgets --}}
    <x-filament-widgets::widgets
        :columns="$this->getHeaderWidgetsColumns()"
        :data="
            [
                ...$this->getWidgetData(),
                'activeTab' => $activeTab,
            ]
        "
        :widgets="$this->getHeaderWidgets()"
    />

    {{-- Tabs Section --}}
    <div class="space-y-6">
        <x-filament::tabs wire:model.live="activeTab">
            <x-filament::tabs.item
                :active="$activeTab === 'resumen'"
                icon="heroicon-m-chart-bar"
                wire:click="$set('activeTab', 'resumen')"
            >
                Resumen
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'movimientos'"
                icon="heroicon-m-arrow-path-rounded-square"
                wire:click="$set('activeTab', 'movimientos')"
            >
                Movimientos
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'alertas'"
                icon="heroicon-m-bell-alert"
                wire:click="$set('activeTab', 'alertas')"
            >
                Alertas
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{-- Tab Content --}}
        <div>
            @if ($activeTab === 'resumen')
                {{-- Tab Resumen --}}
                <div class="space-y-6">
                    <x-filament-widgets::widgets
                        :columns="$this->getFooterWidgetsColumns()"
                        :data="
                            [
                                ...$this->getWidgetData(),
                            ]
                        "
                        :widgets="[
                            \App\Filament\Widgets\StockTrendsChartWidget::class,
                            \App\Filament\Widgets\TopConsumedProductsWidget::class,
                        ]"
                    />
                </div>
            @elseif ($activeTab === 'movimientos')
                {{-- Tab Movimientos --}}
                <div class="space-y-6">
                    <x-filament-widgets::widgets
                        :columns="$this->getFooterWidgetsColumns()"
                        :data="
                            [
                                ...$this->getWidgetData(),
                            ]
                        "
                        :widgets="[
                            \App\Filament\Widgets\StockMovementsTableWidget::class,
                            \App\Filament\Widgets\RecentMovementsWidget::class,
                        ]"
                    />
                </div>
            @elseif ($activeTab === 'alertas')
                {{-- Tab Alertas --}}
                <div class="space-y-6">
                    <x-filament-widgets::widgets
                        :columns="$this->getFooterWidgetsColumns()"
                        :data="
                            [
                                ...$this->getWidgetData(),
                            ]
                        "
                        :widgets="[
                            \App\Filament\Widgets\CriticalAlertsTableWidget::class,
                        ]"
                    />
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>

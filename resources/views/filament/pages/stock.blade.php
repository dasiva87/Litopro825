<x-filament-panels::page>
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
                {{-- Tab Resumen: Gráfico de tendencias --}}
                <x-filament-widgets::widgets
                    :columns="$this->getFooterWidgetsColumns()"
                    :data="$this->getWidgetData()"
                    :widgets="[
                        \App\Filament\Widgets\StockTrendsChartWidget::class,
                    ]"
                />
            @elseif ($activeTab === 'movimientos')
                {{-- Tab Movimientos: Tabla con filtros --}}
                <x-filament-widgets::widgets
                    :columns="$this->getFooterWidgetsColumns()"
                    :data="$this->getWidgetData()"
                    :widgets="[
                        \App\Filament\Widgets\StockMovementsTableWidget::class,
                    ]"
                />
            @elseif ($activeTab === 'alertas')
                {{-- Tab Alertas: Tabla de alertas críticas --}}
                <x-filament-widgets::widgets
                    :columns="$this->getFooterWidgetsColumns()"
                    :data="$this->getWidgetData()"
                    :widgets="[
                        \App\Filament\Widgets\CriticalAlertsTableWidget::class,
                    ]"
                />
            @endif
        </div>
    </div>
</x-filament-panels::page>

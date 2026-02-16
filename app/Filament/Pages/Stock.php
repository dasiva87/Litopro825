<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Services\StockAlertService;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class Stock extends Page
{
    protected string $view = 'filament.pages.stock';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Stock';

    protected static ?string $title = 'Gestión de Stock e Inventario';

    protected static ?int $navigationSort = 5;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Inventario;

    protected ?string $pollingInterval = '30s';

    protected StockAlertService $alertService;

    public string $activeTab = 'resumen';

    public function boot(): void
    {
        $this->alertService = app(StockAlertService::class);
    }

    public function refreshData(): void
    {
        // Refrescar alertas
        $this->alertService->evaluateAllAlerts(auth()->user()->company_id);

        $this->js('$refresh');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Actualizar Datos')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(fn () => $this->refreshData()),

            \Filament\Actions\Action::make('view_alerts')
                ->label('Ver Alertas')
                ->icon('heroicon-o-bell-alert')
                ->color('warning')
                ->url(fn () => route('filament.admin.resources.stock-alerts.index')),

            \Filament\Actions\Action::make('new_movement')
                ->label('Nuevo Movimiento')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->modalHeading('Registrar Movimiento de Stock')
                ->modalDescription('Ingrese los datos del movimiento de inventario')
                ->form([
                    \Filament\Forms\Components\Select::make('product_id')
                        ->label('Producto')
                        ->options(function () {
                            return \App\Models\Product::forCurrentTenant()
                                ->where('active', true)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->required(),
                    \Filament\Forms\Components\Select::make('movement_type')
                        ->label('Tipo de Movimiento')
                        ->options([
                            'entry' => 'Entrada',
                            'exit' => 'Salida',
                            'adjustment' => 'Ajuste',
                        ])
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('quantity')
                        ->label('Cantidad')
                        ->numeric()
                        ->required()
                        ->minValue(1),
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Notas')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    // Aquí iría la lógica para crear el movimiento
                    // Por ahora solo mostramos notificación de éxito
                    \Filament\Notifications\Notification::make()
                        ->title('Movimiento registrado')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\StockOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        // Los widgets se cargan dinámicamente en la vista según el tab activo
        return [];
    }

    public function getWidgetData(): array
    {
        return [];
    }
}

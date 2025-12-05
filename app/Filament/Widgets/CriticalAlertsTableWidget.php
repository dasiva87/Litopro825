<?php

namespace App\Filament\Widgets;

use App\Models\StockAlert;
use App\Services\TenantContext;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CriticalAlertsTableWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = [
        'sm' => 1,
        'md' => 1,
        'lg' => 2,
    ];

    protected static ?string $heading = 'Alertas Críticas Activas';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockAlert::query()
                    ->where('company_id', TenantContext::id())
                    ->where('severity', 'critical')
                    ->whereIn('status', ['active', 'acknowledged'])
                    ->with(['stockable'])
                    ->orderByDesc('triggered_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('stockable.name')
                    ->label('Item Afectado')
                    ->searchable()
                    ->description(fn ($record) => $record->stockable_type === 'App\Models\Product' ? 'Producto' : 'Papel')
                    ->weight('medium')
                    ->url(fn ($record) => route('filament.admin.resources.stock-alerts.view', ['record' => $record]))
                    ->color('primary'),

                Tables\Columns\TextColumn::make('type_label')
                    ->label('Tipo')
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stock Actual')
                    ->numeric()
                    ->alignCenter()
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Mínimo')
                    ->numeric()
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status_label')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'Activa' => 'danger',
                        'Reconocida' => 'warning',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('age')
                    ->label('Antigüedad')
                    ->getStateUsing(fn ($record) => $record->getAgeDays() . ' días')
                    ->badge()
                    ->color(fn ($record) => match(true) {
                        $record->getAgeDays() >= 7 => 'danger',
                        $record->getAgeDays() >= 3 => 'warning',
                        default => 'info'
                    })
                    ->alignCenter(),
            ])
            ->actions([
                Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.stock-alerts.view', ['record' => $record]))
                    ->color('info'),

                Action::make('resolve')
                    ->label('Resolver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['active', 'acknowledged']))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->resolve(auth()->id());
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Alerta Resuelta')
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Sin Alertas Críticas')
            ->emptyStateDescription('No hay alertas críticas activas en este momento.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->paginated(false);
    }
}

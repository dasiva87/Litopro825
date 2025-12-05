<?php

namespace App\Filament\Widgets;

use App\Models\StockMovement;
use App\Services\TenantContext;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentMovementsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = [
        'sm' => 1,
        'md' => 1,
        'lg' => 2,
    ];

    protected static ?string $heading = 'Movimientos Recientes';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockMovement::query()
                    ->where('company_id', TenantContext::id())
                    ->with(['stockable', 'user'])
                    ->orderByDesc('created_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'in' => 'Entrada',
                        'out' => 'Salida',
                        'adjustment' => 'Ajuste',
                        default => $state
                    })
                    ->color(fn (string $state): string => match($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'adjustment' => 'warning',
                        default => 'gray'
                    })
                    ->icon(fn (string $state): string => match($state) {
                        'in' => 'heroicon-m-arrow-down-circle',
                        'out' => 'heroicon-m-arrow-up-circle',
                        'adjustment' => 'heroicon-m-arrow-path',
                        default => 'heroicon-m-minus-circle'
                    }),

                Tables\Columns\TextColumn::make('stockable.name')
                    ->label('Item')
                    ->searchable()
                    ->description(fn ($record) => $record->stockable_type === 'App\Models\Product' ? 'Producto' : 'Papel')
                    ->weight('medium')
                    ->url(function ($record) {
                        if ($record->stockable_type === 'App\Models\Product') {
                            return route('filament.admin.resources.products.edit', ['record' => $record->stockable_id]);
                        } else {
                            return route('filament.admin.resources.papers.edit', ['record' => $record->stockable_id]);
                        }
                    })
                    ->openUrlInNewTab()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($record) => match($record->type) {
                        'in' => 'success',
                        'out' => 'danger',
                        'adjustment' => 'warning',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('stock_after')
                    ->label('Stock Resultante')
                    ->numeric()
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Referencia')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notas')
                    ->wrap()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->emptyStateHeading('Sin Movimientos Recientes')
            ->emptyStateDescription('No hay movimientos de stock registrados.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->paginated(false);
    }
}

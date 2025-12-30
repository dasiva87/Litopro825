<?php

namespace App\Filament\Resources;

use App\Enums\NavigationGroup;
use App\Filament\Resources\StockAlertResource\Pages;
use App\Filament\Resources\StockAlertResource\Schemas\StockAlertViewSchema;
use App\Models\StockAlert;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StockAlertResource extends Resource
{
    protected static ?string $model = StockAlert::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Alertas de Stock';

    protected static ?string $modelLabel = 'Alerta de Stock';

    protected static ?string $pluralModelLabel = 'Alertas de Stock';

    protected static ?int $navigationSort = 4;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Inventario;

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return StockAlertViewSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('stockable.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->stockable_type === 'App\Models\Product' ? 'Producto' : 'Papel')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('type_label')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'Sin Stock' => 'danger',
                        'Stock Crítico' => 'danger',
                        'Stock Bajo' => 'warning',
                        'Punto de Reorden' => 'info',
                        default => 'gray'
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('type', $direction);
                    }),

                Tables\Columns\TextColumn::make('severity_label')
                    ->label('Severidad')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'Crítica' => 'danger',
                        'Alta' => 'warning',
                        'Media' => 'info',
                        'Baja' => 'success',
                        default => 'gray'
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('severity', $direction);
                    }),

                Tables\Columns\TextColumn::make('status_label')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'Activa' => 'danger',
                        'Reconocida' => 'warning',
                        'Resuelta' => 'success',
                        'Descartada' => 'gray',
                        default => 'gray'
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('status', $direction);
                    }),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stock Actual')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Stock Mínimo')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('triggered_at')
                    ->label('Activada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->getAgeDays() . ' días')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('acknowledged_at')
                    ->label('Reconocida')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Resuelta')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('triggered_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->label('Severidad')
                    ->options([
                        'low' => 'Baja',
                        'medium' => 'Media',
                        'high' => 'Alta',
                        'critical' => 'Crítica',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activa',
                        'acknowledged' => 'Reconocida',
                        'resolved' => 'Resuelta',
                        'dismissed' => 'Descartada',
                    ])
                    ->multiple()
                    ->default(['active', 'acknowledged']),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'low_stock' => 'Stock Bajo',
                        'out_of_stock' => 'Sin Stock',
                        'critical_low' => 'Stock Crítico',
                        'reorder_point' => 'Punto de Reorden',
                        'excess_stock' => 'Exceso de Stock',
                        'movement_anomaly' => 'Movimiento Anómalo',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('critical_only')
                    ->label('Solo Críticas')
                    ->query(fn (Builder $query): Builder => $query->where('severity', 'critical'))
                    ->toggle(),

                Tables\Filters\Filter::make('unresolved')
                    ->label('Sin Resolver')
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', ['active', 'acknowledged']))
                    ->toggle()
                    ->default(),
            ])
            ->actions([
                Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.stock-alerts.view', ['record' => $record]))
                    ->color('info'),

                Action::make('acknowledge')
                    ->label('Reconocer')
                    ->icon('heroicon-o-hand-raised')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'active')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->acknowledge(auth()->id());
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Alerta Reconocida')
                            ->body('La alerta ha sido marcada como reconocida.')
                            ->send();
                    }),

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
                            ->body('La alerta ha sido marcada como resuelta.')
                            ->send();
                    }),

                Action::make('dismiss')
                    ->label('Descartar')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn ($record) => in_array($record->status, ['active', 'acknowledged']))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->dismiss(auth()->id());
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Alerta Descartada')
                            ->body('La alerta ha sido descartada.')
                            ->send();
                    }),

                Action::make('view_item')
                    ->label('Ver Item')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(function ($record) {
                        if ($record->stockable_type === 'App\Models\Product') {
                            return route('filament.admin.resources.products.edit', ['record' => $record->stockable_id]);
                        } else {
                            return route('filament.admin.resources.papers.edit', ['record' => $record->stockable_id]);
                        }
                    })
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('acknowledge_bulk')
                        ->label('Reconocer Seleccionadas')
                        ->icon('heroicon-o-hand-raised')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status === 'active') {
                                    $record->acknowledge(auth()->id());
                                }
                            }
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Alertas Reconocidas')
                                ->body(count($records) . ' alertas han sido reconocidas.')
                                ->send();
                        }),

                    BulkAction::make('resolve_bulk')
                        ->label('Resolver Seleccionadas')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (in_array($record->status, ['active', 'acknowledged'])) {
                                    $record->resolve(auth()->id());
                                }
                            }
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Alertas Resueltas')
                                ->body(count($records) . ' alertas han sido resueltas.')
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockAlerts::route('/'),
            'view' => Pages\ViewStockAlert::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()->company_id)
            ->with(['stockable', 'acknowledgedBy', 'resolvedBy']);
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\StockMovement;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class StockMovementsTableWidget extends BaseWidget
{
    protected static ?string $heading = '📋 Historial de Movimientos';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '180s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockMovement::query()
                    ->forCurrentTenant()
                    ->with(['stockable', 'user'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('stockable.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),

                Tables\Columns\TextColumn::make('stockable_type')
                    ->label('Tipo')
                    ->formatStateUsing(function ($state) {
                        return $state === Product::class ? 'Producto' : 'Papel';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Product::class => 'success',
                        default => 'info',
                    }),

                Tables\Columns\TextColumn::make('type')
                    ->label('Movimiento')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'in' => 'Entrada',
                            'out' => 'Salida',
                            'adjustment' => 'Ajuste',
                            default => ucfirst($state),
                        };
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'adjustment' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->formatStateUsing(function ($record) {
                        $prefix = $record->type === 'in' ? '+' : ($record->type === 'out' ? '-' : '');
                        return $prefix . number_format($record->quantity);
                    })
                    ->color(fn ($record): string => match ($record->type) {
                        'in' => 'success',
                        'out' => 'danger',
                        default => 'warning',
                    })
                    ->weight('bold')
                    ->sortable()
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Razón')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'sale' => 'Venta',
                            'purchase' => 'Compra',
                            'return' => 'Devolución',
                            'damage' => 'Daño',
                            'adjustment' => 'Ajuste',
                            'transfer' => 'Transferencia',
                            default => ucfirst($state),
                        };
                    })
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->default('Sistema')
                    ->sortable()
                    ->limit(15),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(30)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo de Movimiento')
                    ->options([
                        'in' => 'Entrada',
                        'out' => 'Salida',
                        'adjustment' => 'Ajuste',
                    ])
                    ->placeholder('Todos los tipos')
                    ->multiple(),

                SelectFilter::make('stockable_type')
                    ->label('Tipo de Item')
                    ->options([
                        Product::class => 'Producto',
                        'App\\Models\\Paper' => 'Papel',
                    ])
                    ->placeholder('Todos los items')
                    ->multiple(),

                SelectFilter::make('reason')
                    ->label('Razón')
                    ->options([
                        'sale' => 'Venta',
                        'purchase' => 'Compra',
                        'return' => 'Devolución',
                        'damage' => 'Daño',
                        'adjustment' => 'Ajuste',
                        'transfer' => 'Transferencia',
                    ])
                    ->placeholder('Todas las razones')
                    ->multiple(),

                Filter::make('created_at')
                    ->label('Rango de Fechas')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Desde')
                            ->placeholder('Fecha inicio'),
                        DatePicker::make('created_until')
                            ->label('Hasta')
                            ->placeholder('Fecha final'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Desde: ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Hasta: ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Action::make('view_details')
                    ->label('Ver Detalles')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Detalles del Movimiento')
                    ->modalContent(fn (StockMovement $record) => view('filament.widgets.stock-movement-details', compact('record')))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('export_selected')
                        ->label('Exportar Seleccionados')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function ($records) {
                            return $this->exportMovements($records);
                        }),

                    BulkAction::make('add_notes')
                        ->label('Agregar Notas')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->form([
                            \Filament\Forms\Components\TextInput::make('notes')
                                ->label('Notas')
                                ->placeholder('Agregar nota a los movimientos seleccionados...')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (array $data, $records) {
                            $records->each(function ($record) use ($data) {
                                $existingNotes = $record->notes ? $record->notes . ' | ' : '';
                                $record->update(['notes' => $existingNotes . $data['notes']]);
                            });
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Agregar Notas')
                        ->modalSubheading('Las notas se agregarán a todos los movimientos seleccionados.'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->poll('30s')
            ->striped()
            ->persistFiltersInSession();
    }

    public function exportMovements($records = null)
    {
        $movements = $records ?? StockMovement::with(['stockable', 'user'])
            ->forCurrentTenant()
            ->orderBy('created_at', 'desc')
            ->get();

        $csvData = [];
        $csvData[] = [
            'Fecha', 'Item', 'Tipo Item', 'Movimiento',
            'Cantidad', 'Razón', 'Usuario', 'Notas'
        ];

        foreach ($movements as $movement) {
            $csvData[] = [
                $movement->created_at->format('d/m/Y H:i'),
                $movement->stockable->name,
                $movement->stockable_type === Product::class ? 'Producto' : 'Papel',
                match ($movement->type) {
                    'in' => 'Entrada',
                    'out' => 'Salida',
                    'adjustment' => 'Ajuste',
                    default => ucfirst($movement->type),
                },
                ($movement->type === 'in' ? '+' : ($movement->type === 'out' ? '-' : '')) . $movement->quantity,
                match ($movement->reason) {
                    'sale' => 'Venta',
                    'purchase' => 'Compra',
                    'return' => 'Devolución',
                    'damage' => 'Daño',
                    'adjustment' => 'Ajuste',
                    'transfer' => 'Transferencia',
                    default => ucfirst($movement->reason),
                },
                $movement->user->name ?? 'Sistema',
                $movement->notes,
            ];
        }

        $filename = 'movimientos_stock_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $csvContent = '';

        foreach ($csvData as $row) {
            $csvContent .= '"' . implode('","', $row) . '"' . "\n";
        }

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
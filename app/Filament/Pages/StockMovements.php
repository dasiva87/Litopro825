<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Models\StockMovement;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\ExportAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use UnitEnum;
use BackedEnum;

class StockMovements extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationLabel = 'Movimientos de Stock';

    protected static ?string $title = 'Historial de Movimientos de Stock';

    protected static ?int $navigationSort = 4;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::INVENTORY;

    public function table(Table $table): Table
    {
        return $table
            ->query(StockMovement::query()->with(['stockable', 'user']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('stockable.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stockable_type')
                    ->label('Tipo')
                    ->formatStateUsing(function ($state) {
                        return $state === Product::class ? 'Producto' : 'Papel';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Product::class => 'success',
                        default => 'info',
                    }),

                TextColumn::make('type')
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

                TextColumn::make('quantity')
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
                    ->sortable(),

                TextColumn::make('reason')
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
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->default('Sistema')
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(30)
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo de Movimiento')
                    ->options([
                        'in' => 'Entrada',
                        'out' => 'Salida',
                        'adjustment' => 'Ajuste',
                    ])
                    ->placeholder('Todos los tipos'),

                SelectFilter::make('stockable_type')
                    ->label('Tipo de Item')
                    ->options([
                        Product::class => 'Producto',
                        'App\\Models\\Paper' => 'Papel',
                    ])
                    ->placeholder('Todos los items'),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Desde'),
                        DatePicker::make('created_until')
                            ->label('Hasta'),
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
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('30s');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Exportar CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    return $this->exportMovements();
                }),

            Action::make('refresh')
                ->label('Actualizar')
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->resetTable()),
        ];
    }

    public function exportMovements()
    {
        $movements = StockMovement::with(['stockable', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        $csvData = [];
        $csvData[] = ['Fecha', 'Item', 'Tipo Item', 'Movimiento', 'Cantidad', 'Razón', 'Usuario', 'Notas'];

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

    protected string $view = 'filament.pages.stock-movements';
}

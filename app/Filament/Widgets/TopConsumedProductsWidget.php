<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\TenantContext;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopConsumedProductsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'sm' => 1,
        'md' => 1,
        'lg' => 2,
    ];

    protected static ?string $heading = 'Top 5 Productos Más Consumidos';

    public function table(Table $table): Table
    {
        $companyId = TenantContext::id();
        $last30Days = now()->subDays(30);

        // Obtener productos más consumidos
        $topProducts = StockMovement::where('company_id', $companyId)
            ->where('type', 'out')
            ->where('created_at', '>=', $last30Days)
            ->where('stockable_type', Product::class)
            ->selectRaw('stockable_id, SUM(quantity) as total_consumed')
            ->groupBy('stockable_id')
            ->orderByDesc('total_consumed')
            ->limit(5)
            ->get()
            ->pluck('total_consumed', 'stockable_id');

        if ($topProducts->isEmpty()) {
            return $table
                ->query(Product::query()->whereRaw('1 = 0'))
                ->columns([
                    Tables\Columns\TextColumn::make('empty')
                        ->label('Sin datos')
                        ->default('No hay consumo registrado en los últimos 30 días')
                        ->alignCenter(),
                ]);
        }

        return $table
            ->query(
                Product::query()
                    ->whereIn('id', $topProducts->keys())
                    ->orderByRaw('FIELD(id, ' . $topProducts->keys()->implode(',') . ')')
            )
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->state(function ($rowLoop) {
                        return $rowLoop->iteration;
                    })
                    ->badge()
                    ->color(fn ($rowLoop) => match($rowLoop->iteration) {
                        1 => 'warning',
                        2 => 'gray',
                        3 => 'success',
                        default => 'primary'
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Producto')
                    ->searchable()
                    ->description(fn ($record) => $record->code)
                    ->weight('medium')
                    ->url(fn ($record) => route('filament.admin.resources.products.edit', ['record' => $record]))
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stock Actual')
                    ->getStateUsing(fn ($record) => $record->stock)
                    ->badge()
                    ->color(fn ($record) => match(true) {
                        $record->stock <= 0 => 'danger',
                        $record->stock <= $record->min_stock => 'warning',
                        default => 'success'
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('consumed')
                    ->label('Consumido (30d)')
                    ->getStateUsing(function ($record) use ($topProducts) {
                        return $topProducts[$record->id] ?? 0;
                    })
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->icon('heroicon-m-arrow-trending-down')
                    ->iconColor('danger'),

                Tables\Columns\TextColumn::make('coverage')
                    ->label('Cobertura')
                    ->getStateUsing(function ($record) use ($topProducts) {
                        $consumed = $topProducts[$record->id] ?? 0;
                        $dailyConsumption = $consumed / 30;

                        if ($dailyConsumption <= 0) {
                            return '∞ días';
                        }

                        $days = (int) ($record->stock / $dailyConsumption);
                        return $days . ' días';
                    })
                    ->badge()
                    ->color(function ($record) use ($topProducts) {
                        $consumed = $topProducts[$record->id] ?? 0;
                        $dailyConsumption = $consumed / 30;

                        if ($dailyConsumption <= 0) {
                            return 'success';
                        }

                        $days = (int) ($record->stock / $dailyConsumption);

                        return match(true) {
                            $days < 7 => 'danger',
                            $days < 14 => 'warning',
                            $days < 30 => 'info',
                            default => 'success'
                        };
                    })
                    ->alignCenter(),
            ])
            ->paginated(false);
    }
}

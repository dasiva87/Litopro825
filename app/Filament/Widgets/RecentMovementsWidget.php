<?php

namespace App\Filament\Widgets;

use App\Models\StockMovement;
use App\Models\Product;
use Filament\Widgets\Widget;

class RecentMovementsWidget extends Widget
{
    protected string $view = 'filament.widgets.recent-movements';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    public function getViewData(): array
    {
        $movements = StockMovement::where('company_id', auth()->user()->company_id)
            ->with(['stockable', 'user'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'item_name' => $movement->stockable->name,
                    'item_type' => $movement->stockable_type === Product::class ? 'Producto' : 'Papel',
                    'type' => $movement->type,
                    'type_label' => $movement->type === 'in' ? 'Entrada' : 'Salida',
                    'quantity' => $movement->quantity,
                    'reason' => $movement->reason,
                    'user_name' => $movement->user->name ?? 'Sistema',
                    'created_at' => $movement->created_at->format('d/m/Y H:i'),
                ];
            })
            ->toArray();

        return [
            'movements' => $movements,
        ];
    }
}
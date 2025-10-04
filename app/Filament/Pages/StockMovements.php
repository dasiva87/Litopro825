<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Models\StockMovement;
use App\Models\Product;
use Filament\Pages\Page;
use UnitEnum;
use BackedEnum;

class StockMovements extends Page
{

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationLabel = 'Movimientos de Stock';

    protected static ?string $title = 'Historial de Movimientos de Stock';

    protected static ?int $navigationSort = 4;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Inventario;

    protected string $view = 'filament.pages.stock-movements';
}

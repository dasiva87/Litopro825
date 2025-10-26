<?php

namespace App\Filament\Resources\ProductionOrders;

use App\Enums\NavigationGroup;
use App\Filament\Resources\ProductionOrders\Pages\CreateProductionOrder;
use App\Filament\Resources\ProductionOrders\Pages\EditProductionOrder;
use App\Filament\Resources\ProductionOrders\Pages\ListProductionOrders;
use App\Filament\Resources\ProductionOrders\Pages\ViewProductionOrder;
use App\Filament\Resources\ProductionOrders\Schemas\ProductionOrderForm;
use App\Filament\Resources\ProductionOrders\Tables\ProductionOrdersTable;
use App\Models\ProductionOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ProductionOrderResource extends Resource
{
    protected static ?string $model = ProductionOrder::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Documentos;

    protected static ?string $modelLabel = 'Orden de Producción';

    protected static ?string $pluralModelLabel = 'Órdenes de Producción';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return ProductionOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductionOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductionOrderItemsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $companyId = auth()->user()->company_id ?? config('app.current_tenant_id');

        if (!$companyId) {
            throw new \Exception('No company context found - security violation prevented');
        }

        return parent::getEloquentQuery()
            ->where('production_orders.company_id', $companyId)
            ->with([
                'supplier',
                'operator',
                'documentItems.itemable',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductionOrders::route('/'),
            'create' => CreateProductionOrder::route('/create'),
            'view' => ViewProductionOrder::route('/{record}'),
            'edit' => EditProductionOrder::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

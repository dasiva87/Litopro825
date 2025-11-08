<?php

namespace App\Filament\Resources\PurchaseOrders;

use App\Enums\NavigationGroup;
use App\Filament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use App\Filament\Resources\PurchaseOrders\Schemas\PurchaseOrderInfolist;
use App\Filament\Resources\PurchaseOrders\Tables\PurchaseOrdersTable;
use App\Models\PurchaseOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Documentos;

    protected static ?string $modelLabel = 'Orden de Pedido';

    protected static ?string $pluralModelLabel = 'Órdenes de Pedido';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', PurchaseOrder::class);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $companyId = auth()->user()->company_id ?? config('app.current_tenant_id');

        if (! $companyId) {
            throw new \Exception('No company context found - security violation prevented');
        }

        // Mostrar órdenes creadas por la empresa O órdenes recibidas como proveedor
        return parent::getEloquentQuery()
            ->where(function ($query) use ($companyId) {
                $query->where('purchase_orders.company_id', $companyId)
                    ->orWhere('purchase_orders.supplier_company_id', $companyId);
            })
            ->with([
                'documentItems.itemable', // Can't eager load .paper - not all itemables have it (Product doesn't)
                'documentItems.itemable.company',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseOrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseOrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PurchaseOrderItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'view' => ViewPurchaseOrder::route('/{record}'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}

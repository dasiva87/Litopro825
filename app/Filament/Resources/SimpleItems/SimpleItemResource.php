<?php

namespace App\Filament\Resources\SimpleItems;

use App\Filament\Resources\SimpleItems\Pages\CreateSimpleItem;
use App\Filament\Resources\SimpleItems\Pages\EditSimpleItem;
use App\Filament\Resources\SimpleItems\Pages\ListSimpleItems;
use App\Filament\Resources\SimpleItems\Schemas\SimpleItemForm;
use App\Filament\Resources\SimpleItems\Tables\SimpleItemsTable;
use App\Models\SimpleItem;
use App\Enums\NavigationGroup;
use App\Traits\CompanyTypeResource;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SimpleItemResource extends Resource
{
    use CompanyTypeResource;

    protected static ?string $model = SimpleItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Items;
    
    protected static ?string $navigationLabel = 'Items Sencillos';
    
    protected static ?string $modelLabel = 'Item Sencillo';
    
    protected static ?string $pluralModelLabel = 'Items Sencillos';
    
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', SimpleItem::class);
    }

    public static function form(Schema $schema): Schema
    {
        return SimpleItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SimpleItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSimpleItems::route('/'),
            'create' => CreateSimpleItem::route('/create'),
            'edit' => EditSimpleItem::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        // Aplicar filtro por empresa manualmente
        $tenantId = config('app.current_tenant_id');

        if ($tenantId) {
            $query->forTenant($tenantId);
        } else {
            // Fallback: usar company_id del usuario autenticado
            if (auth()->check() && auth()->user()->company_id) {
                $query->forCurrentTenant();
            }
        }

        return $query;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }
}

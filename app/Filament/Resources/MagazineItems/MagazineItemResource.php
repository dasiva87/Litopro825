<?php

namespace App\Filament\Resources\MagazineItems;

use App\Enums\NavigationGroup;
use App\Filament\Resources\MagazineItems\Pages\CreateMagazineItem;
use App\Filament\Resources\MagazineItems\Pages\EditMagazineItem;
use App\Filament\Resources\MagazineItems\Pages\ListMagazineItems;
use App\Filament\Resources\MagazineItems\Schemas\MagazineItemForm;
use App\Filament\Resources\MagazineItems\Tables\MagazineItemsTable;
use App\Models\MagazineItem;
use App\Traits\CompanyTypeResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MagazineItemResource extends Resource
{
    use CompanyTypeResource;

    protected static ?string $model = MagazineItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Items;

    protected static ?string $navigationLabel = 'Revistas';

    protected static ?string $modelLabel = 'Revista';

    protected static ?string $pluralModelLabel = 'Revistas';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Ocultar del menú lateral
    }

    public static function form(Schema $schema): Schema
    {
        return MagazineItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MagazineItemsTable::configure($table);
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
            'index' => ListMagazineItems::route('/'),
            'create' => CreateMagazineItem::route('/create'),
            'edit' => EditMagazineItem::route('/{record}/edit'),
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

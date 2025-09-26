<?php

namespace App\Filament\Resources\MagazineItems;

use App\Filament\Resources\MagazineItems\Pages\CreateMagazineItem;
use App\Filament\Resources\MagazineItems\Pages\EditMagazineItem;
use App\Filament\Resources\MagazineItems\Pages\ListMagazineItems;
use App\Filament\Resources\MagazineItems\Schemas\MagazineItemForm;
use App\Filament\Resources\MagazineItems\Tables\MagazineItemsTable;
use App\Models\MagazineItem;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use App\Enums\NavigationGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MagazineItemResource extends Resource
{
    protected static ?string $model = MagazineItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Cotizaciones;

    protected static ?string $navigationLabel = 'Revistas';

    protected static ?string $modelLabel = 'Revista';

    protected static ?string $pluralModelLabel = 'Revistas';

    protected static ?int $navigationSort = 30;

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
            $query->where('company_id', $tenantId);
        } else {
            // Fallback: usar company_id del usuario autenticado
            if (auth()->check() && auth()->user()->company_id) {
                $query->where('company_id', auth()->user()->company_id);
            }
        }

        return $query;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }
}

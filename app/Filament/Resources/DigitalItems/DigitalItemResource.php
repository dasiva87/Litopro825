<?php

namespace App\Filament\Resources\DigitalItems;

use App\Enums\NavigationGroup;
use App\Filament\Resources\DigitalItems\Schemas\DigitalItemForm;
use App\Filament\Resources\DigitalItems\Tables\DigitalItemsTable;
use App\Models\DigitalItem;
use App\Traits\CompanyTypeResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DigitalItemResource extends Resource
{
    use CompanyTypeResource;

    protected static ?string $model = DigitalItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationLabel = 'Items Digitales';

    protected static ?string $modelLabel = 'Item Digital';

    protected static ?string $pluralModelLabel = 'Items Digitales';

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Cotizaciones;

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return DigitalItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DigitalItemsTable::configure($table);
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
            'index' => Pages\ListDigitalItems::route('/'),
            'create' => Pages\CreateDigitalItem::route('/create'),
            'edit' => Pages\EditDigitalItem::route('/{record}/edit'),
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


    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'description'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Código' => $record->code,
            'Tipo' => $record->pricing_type_name,
            'Valor' => $record->formatted_unit_value,
        ];
    }
}

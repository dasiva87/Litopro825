<?php

namespace App\Filament\Resources\TalonarioItems;

use App\Filament\Resources\TalonarioItems\Pages\CreateTalonarioItem;
use App\Filament\Resources\TalonarioItems\Pages\EditTalonarioItem;
use App\Filament\Resources\TalonarioItems\Pages\ListTalonarioItems;
use App\Filament\Resources\TalonarioItems\RelationManagers;
use App\Filament\Resources\TalonarioItems\Schemas\TalonarioItemForm;
use App\Filament\Resources\TalonarioItems\Tables\TalonarioItemsTable;
use App\Models\TalonarioItem;
use App\Traits\CompanyTypeResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TalonarioItemResource extends Resource
{
    use CompanyTypeResource;

    protected static ?string $model = TalonarioItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TalonarioItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TalonarioItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TalonarioSheetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTalonarioItems::route('/'),
            'create' => CreateTalonarioItem::route('/create'),
            'edit' => EditTalonarioItem::route('/{record}/edit'),
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

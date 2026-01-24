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
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DigitalItemResource extends Resource
{
    use CompanyTypeResource;

    protected static ?string $model = DigitalItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationLabel = 'Impresión Digital';

    protected static ?string $modelLabel = 'Impresión Digital';

    protected static ?string $pluralModelLabel = 'Impresión Digital';

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Configuracion;

    protected static ?int $navigationSort = 3;

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
                TenantScope::class,
            ]);

        // Obtener company_id del usuario autenticado (más confiable en peticiones Livewire)
        if (!auth()->check()) {
            return $query->with(['company']);
        }

        $currentCompanyId = auth()->user()->company_id;
        $company = auth()->user()->company;

        if ($currentCompanyId && $company) {
            if ($company->isLitografia()) {
                // Para litografías: mostrar sus propios items digitales + items de proveedores con relación aprobada y activa
                $supplierCompanyIds = \App\Models\SupplierRelationship::where('client_company_id', $currentCompanyId)
                    ->where('is_active', true)
                    ->whereNotNull('approved_at')
                    ->pluck('supplier_company_id')
                    ->toArray();

                $query->where(function ($query) use ($currentCompanyId, $supplierCompanyIds) {
                    $query->where('company_id', $currentCompanyId) // Propios (todos)
                          ->orWhere(function ($q) use ($supplierCompanyIds) {
                              // De proveedores: solo los públicos
                              $q->whereIn('company_id', $supplierCompanyIds)
                                ->where('is_public', true);
                          });
                });
            } else {
                // Para otras empresas: solo sus propios items digitales
                $query->where('company_id', $currentCompanyId);
            }
        }

        return $query->with(['company']);
    }

    public static function canEdit($record): bool
    {
        // Solo permitir editar items digitales propios
        if (!$record || !isset($record->company_id)) {
            return false;
        }
        $currentCompanyId = auth()->check() ? auth()->user()->company_id : null;
        return $record->company_id === $currentCompanyId;
    }

    public static function canDelete($record): bool
    {
        // Solo permitir eliminar items digitales propios
        if (!$record || !isset($record->company_id)) {
            return false;
        }
        $currentCompanyId = auth()->check() ? auth()->user()->company_id : null;
        return $record->company_id === $currentCompanyId;
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

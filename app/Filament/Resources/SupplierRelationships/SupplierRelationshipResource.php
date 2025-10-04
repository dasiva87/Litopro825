<?php

namespace App\Filament\Resources\SupplierRelationships;

use App\Enums\NavigationGroup;
use App\Filament\Resources\SupplierRelationships\Pages\EditSupplierRelationship;
use App\Filament\Resources\SupplierRelationships\Pages\ListSupplierRelationships;
use App\Filament\Resources\SupplierRelationships\Schemas\SupplierRelationshipForm;
use App\Filament\Resources\SupplierRelationships\Tables\SupplierRelationshipsTable;
use App\Models\SupplierRelationship;
use App\Traits\CompanyTypeResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SupplierRelationshipResource extends Resource
{
    // use CompanyTypeResource; // Comentado temporalmente para debugging

    protected static ?string $model = SupplierRelationship::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Proveedores';

    protected static ?string $modelLabel = 'Proveedor';

    protected static ?string $pluralModelLabel = 'Proveedores';

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Documentos;

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        // Solo litografías pueden ver sus proveedores
        $company = auth()->user()->company ?? null;
        return $company && $company->isLitografia();
    }

    public static function canCreate(): bool
    {
        // Solo litografías pueden gestionar proveedores, pero no crear manualmente
        return false;
    }

    public static function canEdit($record): bool
    {
        // Solo litografías pueden editar sus relaciones de proveedor
        $company = auth()->user()->company ?? null;
        return $company && $company->isLitografia();
    }

    public static function canDelete($record): bool
    {
        // Solo litografías pueden eliminar sus relaciones de proveedor
        $company = auth()->user()->company ?? null;
        return $company && $company->isLitografia();
    }

    public static function form(Schema $schema): Schema
    {
        return SupplierRelationshipForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierRelationshipsTable::configure($table);
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
            'index' => ListSupplierRelationships::route('/'),
            'edit' => EditSupplierRelationship::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Aplicar filtro por empresa manualmente
        $tenantId = config('app.current_tenant_id');

        if ($tenantId) {
            $query->where('client_company_id', $tenantId);
        } else {
            // Fallback: usar company_id del usuario autenticado
            if (auth()->check() && auth()->user()->company_id) {
                $query->where('client_company_id', auth()->user()->company_id);
            }
        }

        return $query->with(['supplierCompany', 'clientCompany']);
    }
}

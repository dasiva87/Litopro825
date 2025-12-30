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

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Ocultar del menú lateral - funcionalidad gestionada desde SupplierResource
    }

    public static function canViewAny(): bool
    {
        // Litografías y papelerías pueden ver sus proveedores
        $company = auth()->user()->company ?? null;

        return $company && ($company->isLitografia() || $company->isPapeleria());
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
        $currentCompanyId = $tenantId ?? (auth()->check() ? auth()->user()->company_id : null);

        if ($currentCompanyId) {
            $company = \App\Models\Company::find($currentCompanyId);

            if ($company && $company->isPapeleria()) {
                // Para papelerías: mostrar sus clientes (litografías que las tienen como proveedor)
                $query->where('supplier_company_id', $currentCompanyId);
            } else {
                // Para litografías: mostrar sus proveedores (papelerías)
                $query->where('client_company_id', $currentCompanyId);
            }
        }

        return $query->with(['supplierCompany', 'clientCompany']);
    }
}

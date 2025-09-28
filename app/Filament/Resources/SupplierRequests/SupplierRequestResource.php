<?php

namespace App\Filament\Resources\SupplierRequests;

use App\Enums\NavigationGroup;
use App\Filament\Resources\SupplierRequests\Pages\EditSupplierRequest;
use App\Filament\Resources\SupplierRequests\Pages\ListSupplierRequests;
use App\Filament\Resources\SupplierRequests\Schemas\SupplierRequestForm;
use App\Filament\Resources\SupplierRequests\Tables\SupplierRequestsTable;
use App\Models\SupplierRequest;
use App\Traits\CompanyTypeResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SupplierRequestResource extends Resource
{
    use CompanyTypeResource;

    protected static ?string $model = SupplierRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $navigationLabel = 'Solicitudes de Proveedor';

    protected static ?string $modelLabel = 'Solicitud';

    protected static ?string $pluralModelLabel = 'Solicitudes';

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Cotizaciones;

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        // Litografías y papelerías pueden ver solicitudes (diferentes vistas)
        $company = auth()->user()->company ?? null;
        return $company && ($company->isLitografia() || $company->isPapeleria());
    }

    public static function canCreate(): bool
    {
        // Las solicitudes se crean desde el recurso de proveedores
        return false;
    }

    public static function canEdit($record): bool
    {
        // Solo papelerías pueden editar (responder) solicitudes recibidas
        $company = auth()->user()->company ?? null;
        return $company && $company->isPapeleria();
    }

    public static function canDelete($record): bool
    {
        // Solo quien envió la solicitud puede eliminarla
        $company = auth()->user()->company ?? null;
        return $company && $company->isLitografia();
    }

    public static function getNavigationBadge(): ?string
    {
        $currentCompanyId = config('app.current_tenant_id') ?? (auth()->check() ? auth()->user()->company_id : null);
        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

        if ($company && $company->isPapeleria()) {
            // Para papelerías: mostrar solicitudes pendientes recibidas
            $pendingCount = static::getModel()::where('supplier_company_id', $currentCompanyId)
                ->where('status', 'pending')
                ->count();

            return $pendingCount > 0 ? (string) $pendingCount : null;
        }

        return null;
    }

    public static function form(Schema $schema): Schema
    {
        return SupplierRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierRequestsTable::configure($table);
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
            'index' => ListSupplierRequests::route('/'),
            'edit' => EditSupplierRequest::route('/{record}/edit'),
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
                // Para papelerías: mostrar solo solicitudes recibidas
                $query->where('supplier_company_id', $currentCompanyId);
            } else {
                // Para litografías: mostrar solo solicitudes enviadas
                $query->where('requester_company_id', $currentCompanyId);
            }
        }

        return $query->with(['requesterCompany', 'supplierCompany', 'requestedByUser', 'respondedByUser']);
    }
}

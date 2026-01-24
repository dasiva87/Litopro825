<?php

namespace App\Filament\Resources\Finishings;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Finishings\Pages\CreateFinishing;
use App\Filament\Resources\Finishings\Pages\EditFinishing;
use App\Filament\Resources\Finishings\Pages\ListFinishings;
use App\Filament\Resources\Finishings\Schemas\FinishingForm;
use App\Filament\Resources\Finishings\Tables\FinishingsTable;
use App\Models\Finishing;
use App\Traits\CompanyTypeResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class FinishingResource extends Resource
{
    use CompanyTypeResource;

    protected static ?string $model = Finishing::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $navigationLabel = 'Acabados';
    
    protected static ?string $modelLabel = 'Acabado';
    
    protected static ?string $pluralModelLabel = 'Acabados';
    
    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Configuracion;

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', Finishing::class);
    }

    public static function form(Schema $schema): Schema
    {
        return FinishingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinishingsTable::configure($table);
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
            'index' => ListFinishings::route('/'),
            'create' => CreateFinishing::route('/create'),
            'edit' => EditFinishing::route('/{record}/edit'),
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
                // Para litografías: mostrar sus propios acabados + acabados de proveedores con relación aprobada y activa
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
                // Para otras empresas: solo sus propios acabados
                $query->where('company_id', $currentCompanyId);
            }
        }

        return $query->with(['company']);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    public static function canEdit($record): bool
    {
        // Solo permitir editar acabados propios
        if (!$record || !isset($record->company_id)) {
            return false;
        }
        $currentCompanyId = auth()->check() ? auth()->user()->company_id : null;
        return $record->company_id === $currentCompanyId;
    }

    public static function canDelete($record): bool
    {
        // Solo permitir eliminar acabados propios
        if (!$record || !isset($record->company_id)) {
            return false;
        }
        $currentCompanyId = auth()->check() ? auth()->user()->company_id : null;
        return $record->company_id === $currentCompanyId;
    }
}

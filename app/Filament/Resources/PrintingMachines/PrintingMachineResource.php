<?php

namespace App\Filament\Resources\PrintingMachines;

use App\Enums\NavigationGroup;

use App\Filament\Resources\PrintingMachines\Pages\CreatePrintingMachine;
use App\Filament\Resources\PrintingMachines\Pages\EditPrintingMachine;
use App\Filament\Resources\PrintingMachines\Pages\ListPrintingMachines;
use App\Filament\Resources\PrintingMachines\Schemas\PrintingMachineForm;
use App\Filament\Resources\PrintingMachines\Tables\PrintingMachinesTable;
use App\Models\PrintingMachine;
use App\Traits\CompanyTypeResource;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BackedEnum;
use UnitEnum;

class PrintingMachineResource extends Resource
{
    use CompanyTypeResource;

    protected static ?string $model = PrintingMachine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;
    
    protected static ?string $navigationLabel = 'Máquinas de Impresión';
    
    protected static ?string $modelLabel = 'Máquina';
    
    protected static ?string $pluralModelLabel = 'Máquinas de Impresión';
    
    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Configuracion;
    
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', PrintingMachine::class);
    }

    public static function form(Schema $schema): Schema
    {
        return PrintingMachineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrintingMachinesTable::configure($table);
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
            'index' => ListPrintingMachines::route('/'),
            'create' => CreatePrintingMachine::route('/create'),
            'edit' => EditPrintingMachine::route('/{record}/edit'),
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
                // Para litografías: mostrar sus propias máquinas + máquinas de proveedores con relación aprobada y activa
                $supplierCompanyIds = \App\Models\SupplierRelationship::where('client_company_id', $currentCompanyId)
                    ->where('is_active', true)
                    ->whereNotNull('approved_at')
                    ->pluck('supplier_company_id')
                    ->toArray();

                $query->where(function ($query) use ($currentCompanyId, $supplierCompanyIds) {
                    $query->where('company_id', $currentCompanyId) // Propias (todas)
                          ->orWhere(function ($q) use ($supplierCompanyIds) {
                              // De proveedores: solo las públicas
                              $q->whereIn('company_id', $supplierCompanyIds)
                                ->where('is_public', true);
                          });
                });
            } else {
                // Para otras empresas: solo sus propias máquinas
                $query->where('company_id', $currentCompanyId);
            }
        }

        return $query->with(['company']);
    }

    public static function canEdit($record): bool
    {
        // Solo permitir editar máquinas propias
        if (!$record || !isset($record->company_id)) {
            return false;
        }
        $currentCompanyId = auth()->check() ? auth()->user()->company_id : null;
        return $record->company_id === $currentCompanyId;
    }

    public static function canDelete($record): bool
    {
        // Solo permitir eliminar máquinas propias
        if (!$record || !isset($record->company_id)) {
            return false;
        }
        $currentCompanyId = auth()->check() ? auth()->user()->company_id : null;
        return $record->company_id === $currentCompanyId;
    }
}
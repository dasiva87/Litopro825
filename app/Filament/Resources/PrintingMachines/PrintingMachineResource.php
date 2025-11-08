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
}
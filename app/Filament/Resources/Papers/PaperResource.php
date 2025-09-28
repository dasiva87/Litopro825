<?php

namespace App\Filament\Resources\Papers;

use App\Enums\NavigationGroup;

use App\Filament\Resources\Papers\Pages\CreatePaper;
use App\Filament\Resources\Papers\Pages\EditPaper;
use App\Filament\Resources\Papers\Pages\ListPapers;
use App\Filament\Resources\Papers\Schemas\PaperForm;
use App\Filament\Resources\Papers\Tables\PapersTable;
use App\Models\Paper;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BackedEnum;
use UnitEnum;

class PaperResource extends Resource
{
    protected static ?string $model = Paper::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;
    
    protected static ?string $navigationLabel = 'Papeles';
    
    protected static ?string $modelLabel = 'Papel';
    
    protected static ?string $pluralModelLabel = 'Papeles';
    
    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Configuracion;
    
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PaperForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PapersTable::configure($table);
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
            'index' => ListPapers::route('/'),
            'create' => CreatePaper::route('/create'),
            'edit' => EditPaper::route('/{record}/edit'),
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
        $currentCompanyId = $tenantId ?? (auth()->check() ? auth()->user()->company_id : null);

        if ($currentCompanyId) {
            $company = \App\Models\Company::find($currentCompanyId);

            if ($company && $company->isLitografia()) {
                // Para litografías: mostrar sus propios papeles + papeles de proveedores con relación aprobada y activa
                $supplierCompanyIds = \App\Models\SupplierRelationship::where('client_company_id', $currentCompanyId)
                    ->where('is_active', true)
                    ->whereNotNull('approved_at') // Solo relaciones aprobadas
                    ->pluck('supplier_company_id')
                    ->toArray();

                $query->where(function ($query) use ($currentCompanyId, $supplierCompanyIds) {
                    $query->where('company_id', $currentCompanyId) // Propios
                          ->orWhereIn('company_id', $supplierCompanyIds); // Solo de proveedores aprobados
                });
            } else {
                // Para papelerías: solo sus propios papeles
                $query->where('company_id', $currentCompanyId);
            }
        }

        return $query->with(['company']);
    }

    public static function canEdit($record): bool
    {
        // Solo permitir editar papeles propios
        if (!$record || !isset($record->company_id)) {
            return false;
        }
        $currentCompanyId = config('app.current_tenant_id') ?? (auth()->check() ? auth()->user()->company_id : null);
        return $record->company_id === $currentCompanyId;
    }

    public static function canDelete($record): bool
    {
        // Solo permitir eliminar papeles propios
        if (!$record || !isset($record->company_id)) {
            return false;
        }
        $currentCompanyId = config('app.current_tenant_id') ?? (auth()->check() ? auth()->user()->company_id : null);
        return $record->company_id === $currentCompanyId;
    }
}
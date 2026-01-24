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
use App\Models\Scopes\TenantScope;
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

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Inventario;

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', Paper::class);
    }

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
                // Para litografías: mostrar sus propios papeles + papeles de proveedores con relación aprobada y activa
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
        $currentCompanyId = auth()->check() ? auth()->user()->company_id : null;
        return $record->company_id === $currentCompanyId;
    }

    public static function canDelete($record): bool
    {
        // Solo permitir eliminar papeles propios
        if (!$record || !isset($record->company_id)) {
            return false;
        }
        $currentCompanyId = auth()->check() ? auth()->user()->company_id : null;
        return $record->company_id === $currentCompanyId;
    }
}
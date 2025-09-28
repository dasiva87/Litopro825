<?php

namespace App\Filament\Resources\Products;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Products\ProductResource\Pages;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Productos';

    protected static ?string $modelLabel = 'Producto';

    protected static ?string $pluralModelLabel = 'Productos';

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Cotizaciones;

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
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
                // Para litografías: mostrar sus propios productos + productos de proveedores con relación aprobada y activa
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
                // Para papelerías: solo sus propios productos
                $query->where('company_id', $currentCompanyId);
            }
        }

        return $query->with(['company']);
    }

    public static function canEdit($record): bool
    {
        // Solo permitir editar productos propios
        if (!$record || !isset($record->company_id)) {
            return false;
        }
        $currentCompanyId = config('app.current_tenant_id') ?? (auth()->check() ? auth()->user()->company_id : null);
        return $record->company_id === $currentCompanyId;
    }

    public static function canDelete($record): bool
    {
        // Solo permitir eliminar productos propios
        if (!$record || !isset($record->company_id)) {
            return false;
        }
        $currentCompanyId = config('app.current_tenant_id') ?? (auth()->check() ? auth()->user()->company_id : null);
        return $record->company_id === $currentCompanyId;
    }
}

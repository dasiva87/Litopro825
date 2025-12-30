<?php

namespace App\Filament\Resources\CollectionAccounts;

use App\Enums\NavigationGroup;
use App\Filament\Resources\CollectionAccounts\Pages\CreateCollectionAccount;
use App\Filament\Resources\CollectionAccounts\Pages\EditCollectionAccount;
use App\Filament\Resources\CollectionAccounts\Pages\ListCollectionAccounts;
use App\Filament\Resources\CollectionAccounts\Pages\ViewCollectionAccount;
use App\Filament\Resources\CollectionAccounts\Schemas\CollectionAccountForm;
use App\Filament\Resources\CollectionAccounts\Schemas\CollectionAccountInfolist;
use App\Filament\Resources\CollectionAccounts\Tables\CollectionAccountsTable;
use App\Models\CollectionAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CollectionAccountResource extends Resource
{
    protected static ?string $model = CollectionAccount::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Documentos;

    protected static ?string $modelLabel = 'Cuenta de Cobro';

    protected static ?string $pluralModelLabel = 'Cuentas de Cobro';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', CollectionAccount::class);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $companyId = auth()->user()->company_id ?? config('app.current_tenant_id');

        if (! $companyId) {
            throw new \Exception('No company context found - security violation prevented');
        }

        // Mostrar cuentas creadas por la empresa O cuentas recibidas como cliente
        return parent::getEloquentQuery()
            ->where(function ($query) use ($companyId) {
                $query->where('collection_accounts.company_id', $companyId)
                    ->orWhere('collection_accounts.client_company_id', $companyId);
            })
            ->with(['clientCompany', 'company', 'createdBy']);
    }

    public static function form(Schema $schema): Schema
    {
        return CollectionAccountForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CollectionAccountInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CollectionAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CollectionAccountItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollectionAccounts::route('/'),
            'create' => CreateCollectionAccount::route('/create'),
            'view' => ViewCollectionAccount::route('/{record}'),
            'edit' => EditCollectionAccount::route('/{record}/edit'),
        ];
    }
}

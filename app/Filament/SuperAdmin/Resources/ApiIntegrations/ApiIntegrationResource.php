<?php

namespace App\Filament\SuperAdmin\Resources\ApiIntegrations;

use App\Filament\SuperAdmin\Resources\ApiIntegrations\Pages\CreateApiIntegration;
use App\Filament\SuperAdmin\Resources\ApiIntegrations\Pages\EditApiIntegration;
use App\Filament\SuperAdmin\Resources\ApiIntegrations\Pages\ListApiIntegrations;
use App\Filament\SuperAdmin\Resources\ApiIntegrations\Schemas\ApiIntegrationForm;
use App\Filament\SuperAdmin\Resources\ApiIntegrations\Tables\ApiIntegrationsTable;
use App\Models\ApiIntegration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ApiIntegrationResource extends Resource
{
    protected static ?string $model = ApiIntegration::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Integraciones API';

    protected static ?string $modelLabel = 'Integración API';

    protected static ?string $pluralModelLabel = 'Integraciones API';

    protected static UnitEnum|string|null $navigationGroup = 'Enterprise Features';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return ApiIntegrationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiIntegrationsTable::configure($table);
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
            'index' => ListApiIntegrations::route('/'),
            'create' => CreateApiIntegration::route('/create'),
            'edit' => EditApiIntegration::route('/{record}/edit'),
        ];
    }
}

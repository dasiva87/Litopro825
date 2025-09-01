<?php

namespace App\Filament\Resources\DigitalItems;

use App\Filament\Resources\DigitalItems\Pages;
use App\Filament\Resources\DigitalItems\Schemas\DigitalItemForm;
use App\Filament\Resources\DigitalItems\Tables\DigitalItemsTable;
use App\Models\DigitalItem;
use App\Enums\NavigationGroup;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DigitalItemResource extends Resource
{
    protected static ?string $model = DigitalItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationLabel = 'Items Digitales';
    
    protected static ?string $modelLabel = 'Item Digital';
    
    protected static ?string $pluralModelLabel = 'Items Digitales';

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Configuracion;

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return DigitalItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DigitalItemsTable::configure($table);
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
            'index' => Pages\ListDigitalItems::route('/'),
            'create' => Pages\CreateDigitalItem::route('/create'),
            'edit' => Pages\EditDigitalItem::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'description'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Código' => $record->code,
            'Tipo' => $record->pricing_type_name,
            'Valor' => $record->formatted_unit_value,
        ];
    }
}
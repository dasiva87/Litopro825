<?php

namespace App\Filament\Resources\TalonarioItems;

use App\Filament\Resources\TalonarioItems\Pages\CreateTalonarioItem;
use App\Filament\Resources\TalonarioItems\Pages\EditTalonarioItem;
use App\Filament\Resources\TalonarioItems\Pages\ListTalonarioItems;
use App\Filament\Resources\TalonarioItems\RelationManagers;
use App\Filament\Resources\TalonarioItems\Schemas\TalonarioItemForm;
use App\Filament\Resources\TalonarioItems\Tables\TalonarioItemsTable;
use App\Models\TalonarioItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TalonarioItemResource extends Resource
{
    protected static ?string $model = TalonarioItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TalonarioItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TalonarioItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TalonarioSheetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTalonarioItems::route('/'),
            'create' => CreateTalonarioItem::route('/create'),
            'edit' => EditTalonarioItem::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

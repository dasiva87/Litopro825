<?php

namespace App\Filament\Resources\Finishings;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Finishings\Pages\CreateFinishing;
use App\Filament\Resources\Finishings\Pages\EditFinishing;
use App\Filament\Resources\Finishings\Pages\ListFinishings;
use App\Filament\Resources\Finishings\Schemas\FinishingForm;
use App\Filament\Resources\Finishings\Tables\FinishingsTable;
use App\Models\Finishing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class FinishingResource extends Resource
{
    protected static ?string $model = Finishing::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $navigationLabel = 'Acabados';
    
    protected static ?string $modelLabel = 'Acabado';
    
    protected static ?string $pluralModelLabel = 'Acabados';
    
    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Catalogos;

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

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

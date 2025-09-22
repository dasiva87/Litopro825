<?php

namespace App\Filament\SuperAdmin\Resources\AutomatedReports;

use App\Filament\SuperAdmin\Resources\AutomatedReports\Pages\CreateAutomatedReport;
use App\Filament\SuperAdmin\Resources\AutomatedReports\Pages\EditAutomatedReport;
use App\Filament\SuperAdmin\Resources\AutomatedReports\Pages\ListAutomatedReports;
use App\Filament\SuperAdmin\Resources\AutomatedReports\Schemas\AutomatedReportForm;
use App\Filament\SuperAdmin\Resources\AutomatedReports\Tables\AutomatedReportsTable;
use App\Models\AutomatedReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AutomatedReportResource extends Resource
{
    protected static ?string $model = AutomatedReport::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reportes Automáticos';

    protected static ?string $modelLabel = 'Reporte Automático';

    protected static ?string $pluralModelLabel = 'Reportes Automáticos';

    protected static UnitEnum|string|null $navigationGroup = 'Enterprise Features';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return AutomatedReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AutomatedReportsTable::configure($table);
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
            'index' => ListAutomatedReports::route('/'),
            'create' => CreateAutomatedReport::route('/create'),
            'edit' => EditAutomatedReport::route('/{record}/edit'),
        ];
    }
}

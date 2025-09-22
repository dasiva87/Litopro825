<?php

namespace App\Filament\SuperAdmin\Resources\AutomatedReports\Pages;

use App\Filament\SuperAdmin\Resources\AutomatedReports\AutomatedReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAutomatedReports extends ListRecords
{
    protected static string $resource = AutomatedReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

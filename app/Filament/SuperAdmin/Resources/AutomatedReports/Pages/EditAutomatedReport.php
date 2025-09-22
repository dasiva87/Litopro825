<?php

namespace App\Filament\SuperAdmin\Resources\AutomatedReports\Pages;

use App\Filament\SuperAdmin\Resources\AutomatedReports\AutomatedReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAutomatedReport extends EditRecord
{
    protected static string $resource = AutomatedReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

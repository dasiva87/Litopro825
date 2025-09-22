<?php

namespace App\Filament\SuperAdmin\Resources\PlanExperiments\Pages;

use App\Filament\SuperAdmin\Resources\PlanExperiments\PlanExperimentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanExperiment extends EditRecord
{
    protected static string $resource = PlanExperimentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
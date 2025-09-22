<?php

namespace App\Filament\SuperAdmin\Resources\PlanExperiments\Pages;

use App\Filament\SuperAdmin\Resources\PlanExperiments\PlanExperimentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlanExperiment extends CreateRecord
{
    protected static string $resource = PlanExperimentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
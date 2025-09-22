<?php

namespace App\Filament\SuperAdmin\Resources\PlanExperiments\Pages;

use App\Filament\SuperAdmin\Resources\PlanExperiments\PlanExperimentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanExperiments extends ListRecords
{
    protected static string $resource = PlanExperimentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Experimento A/B')
                ->icon('heroicon-o-beaker'),
        ];
    }
}
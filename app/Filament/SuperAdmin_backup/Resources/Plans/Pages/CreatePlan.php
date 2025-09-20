<?php

namespace App\Filament\SuperAdmin\Resources\Plans\Pages;

use App\Filament\SuperAdmin\Resources\Plans\PlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asegurar que features y limits sean arrays
        $data['features'] = $data['features'] ?? [];
        $data['limits'] = $data['limits'] ?? [];

        return $data;
    }
}

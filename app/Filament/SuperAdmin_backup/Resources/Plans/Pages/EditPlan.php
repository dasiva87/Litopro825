<?php

namespace App\Filament\SuperAdmin\Resources\Plans\Pages;

use App\Filament\SuperAdmin\Resources\Plans\PlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Asegurar que features y limits sean arrays
        $data['features'] = $data['features'] ?? [];
        $data['limits'] = $data['limits'] ?? [];

        return $data;
    }
}

<?php

namespace App\Filament\Resources\Finishings\Pages;

use App\Filament\Resources\Finishings\FinishingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinishing extends CreateRecord
{
    protected static string $resource = FinishingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

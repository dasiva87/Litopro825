<?php

namespace App\Filament\Resources\TalonarioItems\Pages;

use App\Filament\Resources\TalonarioItems\TalonarioItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTalonarioItem extends CreateRecord
{
    protected static string $resource = TalonarioItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asegurar multi-tenancy
        $data['company_id'] = auth()->user()->company_id ?? 1;
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}

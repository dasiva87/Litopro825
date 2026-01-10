<?php

namespace App\Filament\Resources\DigitalItems\Pages;

use App\Filament\Resources\DigitalItems\DigitalItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDigitalItem extends CreateRecord
{
    protected static string $resource = DigitalItemResource::class;

    public function getTitle(): string
    {
        return 'Crear Impresión Digital';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Agregar company_id automáticamente
        $data['company_id'] = auth()->user()->company_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Impresión digital creada exitosamente';
    }
}
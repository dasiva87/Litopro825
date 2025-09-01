<?php

namespace App\Filament\Resources\DigitalItems\Pages;

use App\Filament\Resources\DigitalItems\DigitalItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDigitalItem extends EditRecord
{
    protected static string $resource = DigitalItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    public function getTitle(): string
    {
        return 'Editar Item Digital';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Item digital actualizado exitosamente';
    }
}
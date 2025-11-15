<?php

namespace App\Filament\Resources\Finishings\Pages;

use App\Filament\Resources\Finishings\FinishingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditFinishing extends EditRecord
{
    protected static string $resource = FinishingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar la relación ranges si existe
        $this->record->load('ranges');

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Asegurar que company_id se preserve
        if (!isset($data['company_id'])) {
            $data['company_id'] = auth()->user()->company_id;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // La relación ranges se maneja automáticamente por Filament
        // debido a que usamos ->relationship('ranges') en el Repeater
    }
}

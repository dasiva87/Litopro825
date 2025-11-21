<?php

namespace App\Filament\Resources\SimpleItems\Pages;

use App\Filament\Resources\SimpleItems\SimpleItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSimpleItem extends EditRecord
{
    protected static string $resource = SimpleItemResource::class;

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
        // Cargar acabados desde tabla pivot a finishings_data
        $data['finishings_data'] = $this->record->finishings->map(function ($finishing) {
            return [
                'finishing_id' => $finishing->id,
                'quantity' => $finishing->pivot->quantity,
                'width' => $finishing->pivot->width,
                'height' => $finishing->pivot->height,
                'calculated_cost' => $finishing->pivot->calculated_cost,
                'is_default' => $finishing->pivot->is_default,
            ];
        })->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        // Sincronizar acabados en tabla pivot (Arquitectura 1)
        $finishingsData = $this->data['finishings_data'] ?? [];

        // Primero, detach todos los acabados existentes
        $this->record->finishings()->detach();

        // Luego, attach los nuevos acabados
        if (!empty($finishingsData)) {
            foreach ($finishingsData as $finishingData) {
                if (isset($finishingData['finishing_id'])) {
                    $this->record->finishings()->attach($finishingData['finishing_id'], [
                        'quantity' => $finishingData['quantity'] ?? 1,
                        'width' => $finishingData['width'] ?? null,
                        'height' => $finishingData['height'] ?? null,
                        'calculated_cost' => $finishingData['calculated_cost'] ?? 0,
                        'is_default' => $finishingData['is_default'] ?? false,
                        'sort_order' => 0,
                    ]);
                }
            }
        }
    }
}

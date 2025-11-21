<?php

namespace App\Filament\Resources\SimpleItems\Pages;

use App\Filament\Resources\SimpleItems\SimpleItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSimpleItem extends CreateRecord
{
    protected static string $resource = SimpleItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Guardar acabados en tabla pivot (Arquitectura 1)
        $finishingsData = $this->data['finishings_data'] ?? [];

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

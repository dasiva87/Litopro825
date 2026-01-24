<?php

namespace App\Filament\Resources\Products\ProductResource\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $product = $this->record;

        // Cargar acabados en formato para el Repeater
        $product->load('finishings');

        if ($product->finishings->isNotEmpty()) {
            $data['finishings_data'] = $product->finishings->map(function ($finishing) {
                $item = [
                    'finishing_id' => (int) $finishing->id,
                ];

                // Agregar quantity solo si existe y es mayor a 0
                if ($finishing->pivot->quantity) {
                    $item['quantity'] = (float) $finishing->pivot->quantity;
                }

                // Agregar width solo si existe
                if ($finishing->pivot->width) {
                    $item['width'] = (float) $finishing->pivot->width;
                }

                // Agregar height solo si existe
                if ($finishing->pivot->height) {
                    $item['height'] = (float) $finishing->pivot->height;
                }

                return $item;
            })->toArray();
        } else {
            $data['finishings_data'] = [];
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $product = $this->record;
        $finishingsData = $this->data['finishings_data'] ?? [];

        // Eliminar todos los acabados existentes
        $product->finishings()->detach();

        // Agregar los nuevos acabados
        if (!empty($finishingsData)) {
            foreach ($finishingsData as $finishingData) {
                if (empty($finishingData['finishing_id'])) {
                    continue;
                }

                $finishing = \App\Models\Finishing::find($finishingData['finishing_id']);
                if (!$finishing) {
                    continue;
                }

                // Preparar parámetros según el tipo de medida
                $params = [];
                switch ($finishing->measurement_unit->value) {
                    case 'millar':
                    case 'rango':
                    case 'unidad':
                        $params = ['quantity' => $finishingData['quantity'] ?? 1];
                        break;
                    case 'tamaño':
                        $params = [
                            'width' => $finishingData['width'] ?? 0,
                            'height' => $finishingData['height'] ?? 0,
                        ];
                        break;
                }

                // Usar attach directamente (no addFinishing para evitar duplicados)
                $calculatorService = app(\App\Services\FinishingCalculatorService::class);
                $calculatedCost = $calculatorService->calculateCost($finishing, $params);

                $pivotData = [
                    'calculated_cost' => $calculatedCost,
                    'quantity' => $params['quantity'] ?? 0,
                    'width' => $params['width'] ?? null,
                    'height' => $params['height'] ?? null,
                ];

                $product->finishings()->attach($finishing->id, $pivotData);
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
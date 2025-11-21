<?php

namespace App\Filament\Resources\Products\ProductResource\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;

        // Generar código automático si no se proporciona
        if (empty($data['code'])) {
            $data['code'] = 'PROD-' . strtoupper(substr(uniqid(), -6));
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $product = $this->record;
        $finishingsData = $this->data['finishings_data'] ?? [];

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

                // Usar el método addFinishing del modelo
                $product->addFinishing($finishing, $params);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
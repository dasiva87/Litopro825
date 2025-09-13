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
    
    protected function afterCreate(): void
    {
        // Manejar selected_finishings después de crear el talonario
        $formData = $this->form->getState();
        
        if (isset($formData['selected_finishings']) && is_array($formData['selected_finishings'])) {
            foreach ($formData['selected_finishings'] as $finishingId) {
                $finishing = \App\Models\Finishing::find($finishingId);
                if ($finishing) {
                    // Calcular cantidad y costo según el tipo de acabado
                    if ($finishing->measurement_unit === \App\Enums\FinishingMeasurementUnit::POR_NUMERO) {
                        // Por número: usar total de números
                        $totalNumbers = ($this->record->numero_final - $this->record->numero_inicial) + 1;
                        $quantity = $totalNumbers * $this->record->quantity;
                    } else {
                        // Por talonario: usar cantidad de talonarios
                        $totalNumbers = ($this->record->numero_final - $this->record->numero_inicial) + 1;
                        $totalTalonarios = ceil($totalNumbers / $this->record->numeros_por_talonario);
                        $quantity = $totalTalonarios * $this->record->quantity;
                    }
                    
                    $totalCost = $quantity * $finishing->unit_price;
                    
                    $this->record->finishings()->attach($finishingId, [
                        'quantity' => $quantity,
                        'unit_cost' => $finishing->unit_price,
                        'total_cost' => $totalCost,
                        'finishing_options' => null,
                        'notes' => null,
                    ]);
                }
            }
            
            // Recalcular precios del talonario
            $this->record->calculateAll();
            $this->record->save();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}

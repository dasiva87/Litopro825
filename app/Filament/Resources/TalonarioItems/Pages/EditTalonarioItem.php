<?php

namespace App\Filament\Resources\TalonarioItems\Pages;

use App\Filament\Resources\TalonarioItems\TalonarioItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditTalonarioItem extends EditRecord
{
    protected static string $resource = TalonarioItemResource::class;

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
        // Cargar finishings seleccionados para mostrar en el formulario
        if ($this->record && $this->record->finishings) {
            $data['selected_finishings'] = $this->record->finishings->pluck('id')->toArray();
        }
        
        return $data;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Manejar selected_finishings antes de guardar
        if (isset($data['selected_finishings'])) {
            // Separar selected_finishings de los datos del modelo
            $selectedFinishings = $data['selected_finishings'];
            unset($data['selected_finishings']);
            
            // Guardar para procesar después
            $this->selectedFinishings = $selectedFinishings;
        }
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        // Procesar finishings después de guardar
        if (isset($this->selectedFinishings)) {
            // Limpiar finishings existentes
            $this->record->finishings()->detach();
            
            // Agregar nuevos finishings
            foreach ($this->selectedFinishings as $finishingId) {
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
}

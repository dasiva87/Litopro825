<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use Filament\Forms\Components;
use Filament\Schemas\Components\Wizard\Step;
use App\Filament\Resources\SimpleItems\Schemas\SimpleItemForm;
use App\Models\SimpleItem;

class SimpleItemHandler extends AbstractItemHandler
{
    public function getEditForm($record): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Editar Item Sencillo')
                ->description('Modificar los detalles del item y recalcular automáticamente')
                ->schema(SimpleItemForm::configure(new \Filament\Schemas\Schema())->getComponents())
        ];
    }
    
    public function fillForm($record): array
    {
        $item = $record->itemable;
        return $item ? $item->toArray() : [];
    }
    
    public function handleUpdate($record, array $data): void
    {
        $item = $record->itemable;
        if ($item) {
            $item->update($data);
            $item->calculateAll();
            $record->calculateAndUpdatePrices();
        }
    }
    
    public function getWizardStep(): Step
    {
        return Step::make('Item Sencillo')
            ->description('Item básico con cálculos automáticos')
            ->icon('heroicon-o-document-text')
            ->schema(SimpleItemForm::configure(new \Filament\Schemas\Schema())->getComponents());
    }
}
<?php

namespace App\Filament\Resources\PrintingMachines\Pages;

use App\Filament\Resources\PrintingMachines\PrintingMachineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrintingMachine extends EditRecord
{
    protected static string $resource = PrintingMachineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
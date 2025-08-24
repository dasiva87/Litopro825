<?php

namespace App\Filament\Resources\PrintingMachines\Pages;

use App\Filament\Resources\PrintingMachines\PrintingMachineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrintingMachines extends ListRecords
{
    protected static string $resource = PrintingMachineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
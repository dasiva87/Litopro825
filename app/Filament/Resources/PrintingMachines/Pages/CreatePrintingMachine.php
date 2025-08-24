<?php

namespace App\Filament\Resources\PrintingMachines\Pages;

use App\Filament\Resources\PrintingMachines\PrintingMachineResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePrintingMachine extends CreateRecord
{
    protected static string $resource = PrintingMachineResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        
        return $data;
    }
}
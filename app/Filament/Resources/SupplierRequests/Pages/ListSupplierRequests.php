<?php

namespace App\Filament\Resources\SupplierRequests\Pages;

use App\Filament\Resources\SupplierRequests\SupplierRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplierRequests extends ListRecords
{
    protected static string $resource = SupplierRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

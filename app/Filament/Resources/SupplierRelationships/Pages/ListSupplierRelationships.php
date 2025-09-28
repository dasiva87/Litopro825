<?php

namespace App\Filament\Resources\SupplierRelationships\Pages;

use App\Filament\Resources\SupplierRelationships\SupplierRelationshipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplierRelationships extends ListRecords
{
    protected static string $resource = SupplierRelationshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

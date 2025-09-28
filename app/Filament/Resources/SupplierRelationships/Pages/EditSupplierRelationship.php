<?php

namespace App\Filament\Resources\SupplierRelationships\Pages;

use App\Filament\Resources\SupplierRelationships\SupplierRelationshipResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplierRelationship extends EditRecord
{
    protected static string $resource = SupplierRelationshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

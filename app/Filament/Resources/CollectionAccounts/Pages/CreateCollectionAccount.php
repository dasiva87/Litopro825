<?php

namespace App\Filament\Resources\CollectionAccounts\Pages;

use App\Filament\Resources\CollectionAccounts\CollectionAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCollectionAccount extends CreateRecord
{
    protected static string $resource = CollectionAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Limpiar campos que no existen en la BD
        unset($data['client_type']);

        return $data;
    }
}

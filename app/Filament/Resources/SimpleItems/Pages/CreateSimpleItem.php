<?php

namespace App\Filament\Resources\SimpleItems\Pages;

use App\Filament\Resources\SimpleItems\SimpleItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSimpleItem extends CreateRecord
{
    protected static string $resource = SimpleItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;

        return $data;
    }
}

<?php

namespace App\Filament\Resources\MagazineItems\Pages;

use App\Filament\Resources\MagazineItems\MagazineItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMagazineItem extends CreateRecord
{
    protected static string $resource = MagazineItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;

        return $data;
    }
}

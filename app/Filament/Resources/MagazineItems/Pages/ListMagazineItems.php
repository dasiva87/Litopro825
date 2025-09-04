<?php

namespace App\Filament\Resources\MagazineItems\Pages;

use App\Filament\Resources\MagazineItems\MagazineItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMagazineItems extends ListRecords
{
    protected static string $resource = MagazineItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

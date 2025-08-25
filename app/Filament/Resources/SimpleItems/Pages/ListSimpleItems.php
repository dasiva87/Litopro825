<?php

namespace App\Filament\Resources\SimpleItems\Pages;

use App\Filament\Resources\SimpleItems\SimpleItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSimpleItems extends ListRecords
{
    protected static string $resource = SimpleItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

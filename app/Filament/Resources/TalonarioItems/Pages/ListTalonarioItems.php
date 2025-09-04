<?php

namespace App\Filament\Resources\TalonarioItems\Pages;

use App\Filament\Resources\TalonarioItems\TalonarioItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTalonarioItems extends ListRecords
{
    protected static string $resource = TalonarioItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

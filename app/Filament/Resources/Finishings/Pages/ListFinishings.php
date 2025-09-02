<?php

namespace App\Filament\Resources\Finishings\Pages;

use App\Filament\Resources\Finishings\FinishingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinishings extends ListRecords
{
    protected static string $resource = FinishingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

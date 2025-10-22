<?php

namespace App\Filament\Resources\CollectionAccounts\Pages;

use App\Filament\Resources\CollectionAccounts\CollectionAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCollectionAccounts extends ListRecords
{
    protected static string $resource = CollectionAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

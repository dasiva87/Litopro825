<?php

namespace App\Filament\SuperAdmin\Resources\ApiIntegrations\Pages;

use App\Filament\SuperAdmin\Resources\ApiIntegrations\ApiIntegrationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApiIntegrations extends ListRecords
{
    protected static string $resource = ApiIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

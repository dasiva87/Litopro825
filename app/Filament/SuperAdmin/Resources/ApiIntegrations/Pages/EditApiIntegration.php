<?php

namespace App\Filament\SuperAdmin\Resources\ApiIntegrations\Pages;

use App\Filament\SuperAdmin\Resources\ApiIntegrations\ApiIntegrationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApiIntegration extends EditRecord
{
    protected static string $resource = ApiIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

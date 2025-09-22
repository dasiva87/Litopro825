<?php

namespace App\Filament\SuperAdmin\Resources\ApiIntegrations\Pages;

use App\Filament\SuperAdmin\Resources\ApiIntegrations\ApiIntegrationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApiIntegration extends CreateRecord
{
    protected static string $resource = ApiIntegrationResource::class;
}

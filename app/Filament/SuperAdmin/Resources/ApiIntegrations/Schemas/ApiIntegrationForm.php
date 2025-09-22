<?php

namespace App\Filament\SuperAdmin\Resources\ApiIntegrations\Schemas;

use Filament\Schemas\Schema;

class ApiIntegrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}

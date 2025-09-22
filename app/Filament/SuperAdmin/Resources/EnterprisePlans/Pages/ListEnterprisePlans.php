<?php

namespace App\Filament\SuperAdmin\Resources\EnterprisePlans\Pages;

use App\Filament\SuperAdmin\Resources\EnterprisePlans\EnterprisePlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEnterprisePlans extends ListRecords
{
    protected static string $resource = EnterprisePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

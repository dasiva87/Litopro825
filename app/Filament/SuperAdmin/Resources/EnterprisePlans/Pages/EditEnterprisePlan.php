<?php

namespace App\Filament\SuperAdmin\Resources\EnterprisePlans\Pages;

use App\Filament\SuperAdmin\Resources\EnterprisePlans\EnterprisePlanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditEnterprisePlan extends EditRecord
{
    protected static string $resource = EnterprisePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

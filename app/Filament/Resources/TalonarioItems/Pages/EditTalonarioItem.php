<?php

namespace App\Filament\Resources\TalonarioItems\Pages;

use App\Filament\Resources\TalonarioItems\TalonarioItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditTalonarioItem extends EditRecord
{
    protected static string $resource = TalonarioItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

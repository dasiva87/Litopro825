<?php

namespace App\Filament\Resources\SimpleItems\Pages;

use App\Filament\Resources\SimpleItems\SimpleItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSimpleItem extends EditRecord
{
    protected static string $resource = SimpleItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

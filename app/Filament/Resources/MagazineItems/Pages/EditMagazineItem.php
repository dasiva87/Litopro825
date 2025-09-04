<?php

namespace App\Filament\Resources\MagazineItems\Pages;

use App\Filament\Resources\MagazineItems\MagazineItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditMagazineItem extends EditRecord
{
    protected static string $resource = MagazineItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

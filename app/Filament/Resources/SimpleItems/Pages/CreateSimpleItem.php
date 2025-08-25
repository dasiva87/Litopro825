<?php

namespace App\Filament\Resources\SimpleItems\Pages;

use App\Filament\Resources\SimpleItems\SimpleItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSimpleItem extends CreateRecord
{
    protected static string $resource = SimpleItemResource::class;
}

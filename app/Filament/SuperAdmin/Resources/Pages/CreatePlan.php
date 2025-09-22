<?php

namespace App\Filament\SuperAdmin\Resources\Pages;

use App\Filament\SuperAdmin\Resources\PlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;
}
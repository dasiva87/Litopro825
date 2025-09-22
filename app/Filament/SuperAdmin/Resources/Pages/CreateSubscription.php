<?php

namespace App\Filament\SuperAdmin\Resources\Pages;

use App\Filament\SuperAdmin\Resources\SubscriptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;
}
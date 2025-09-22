<?php

namespace App\Filament\SuperAdmin\Resources\NotificationChannels\Pages;

use App\Filament\SuperAdmin\Resources\NotificationChannels\NotificationChannelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotificationChannels extends ListRecords
{
    protected static string $resource = NotificationChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

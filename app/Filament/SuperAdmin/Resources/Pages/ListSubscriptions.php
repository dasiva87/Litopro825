<?php

namespace App\Filament\SuperAdmin\Resources\Pages;

use App\Filament\SuperAdmin\Resources\SubscriptionResource;
use App\Filament\SuperAdmin\Widgets\SubscriptionStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Suscripción')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SubscriptionStatsWidget::class,
        ];
    }
}
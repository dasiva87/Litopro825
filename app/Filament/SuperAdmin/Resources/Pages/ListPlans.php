<?php

namespace App\Filament\SuperAdmin\Resources\Pages;

use App\Filament\SuperAdmin\Resources\PlanResource;
use App\Filament\SuperAdmin\Widgets\PlanStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Plan')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PlanStatsWidget::class,
        ];
    }
}
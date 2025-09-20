<?php

namespace App\Filament\SuperAdmin\Resources\ActivityLogResource\Pages;

use App\Filament\SuperAdmin\Resources\ActivityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export Logs')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    // Export functionality can be implemented here
                    $this->notify('success', 'Export functionality coming soon');
                }),

            Actions\Action::make('clean_old')
                ->label('Clean Old Logs')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Clean Old Activity Logs')
                ->modalDescription('This will delete activity logs older than 90 days. This action cannot be undone.')
                ->action(function () {
                    $deleted = \App\Models\ActivityLog::where('created_at', '<', now()->subDays(90))->delete();
                    $this->notify('success', "Deleted {$deleted} old activity logs");
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // ActivityLogStatsWidget::class,
        ];
    }
}

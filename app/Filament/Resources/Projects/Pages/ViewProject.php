<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\ProjectResource;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('change_status')
                ->label('Cambiar Estado')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->form([
                    Select::make('status')
                        ->label('Nuevo Estado')
                        ->options(ProjectStatus::class)
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $this->record->update(['status' => $data['status']]);

                    Notification::make()
                        ->title('Estado actualizado')
                        ->success()
                        ->send();
                }),

            Actions\EditAction::make(),
        ];
    }

}

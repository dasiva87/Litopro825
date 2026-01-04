<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Documents\Widgets\FinancialSummaryWidget;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Limpiar campos que no existen en la BD
        unset($data['client_type']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->isDraft()),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),

            Actions\Action::make('send')
                ->label('Enviar Cotización')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn () => $this->record->canSend())
                ->requiresConfirmation()
                ->modalHeading('Enviar Cotización')
                ->modalDescription('¿Está seguro que desea enviar esta cotización al cliente?')
                ->action(function () {
                    $this->record->markAsSent();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Actions\Action::make('new_version')
                ->label('Nueva Versión')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->visible(fn () => !$this->record->isDraft())
                ->requiresConfirmation()
                ->modalHeading('Crear Nueva Versión')
                ->modalDescription('Se creará una nueva versión de este documento en estado borrador.')
                ->action(function () {
                    $newDocument = $this->record->createNewVersion();
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $newDocument]));
                }),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            FinancialSummaryWidget::make([
                'record' => $this->record,
            ]),
        ];
    }
}
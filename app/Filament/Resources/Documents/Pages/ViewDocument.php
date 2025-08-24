<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->canEdit()),
                
            Actions\Action::make('print_pdf')
                ->label('Imprimir PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('documents.pdf', $this->record))
                ->openUrlInNewTab(),
                
            Actions\Action::make('send')
                ->label('Enviar')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn () => $this->record->canSend())
                ->requiresConfirmation()
                ->action(fn () => $this->record->markAsSent()),
                
            Actions\Action::make('approve')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->canApprove())
                ->requiresConfirmation()
                ->action(fn () => $this->record->markAsApproved()),
        ];
    }
}
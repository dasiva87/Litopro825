<?php

namespace App\Filament\Resources\DigitalItems\Pages;

use App\Filament\Resources\DigitalItems\DigitalItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDigitalItems extends ListRecords
{
    protected static string $resource = DigitalItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Impresión Digital')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return 'Impresión Digital';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Aquí se pueden agregar widgets estadísticos si es necesario
        ];
    }
}
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
                ->label('Nuevo Item Digital')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return 'Items Digitales';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Aquí se pueden agregar widgets estadísticos si es necesario
        ];
    }
}
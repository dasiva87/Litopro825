<?php

namespace App\Filament\Resources\DigitalItems\Pages;

use App\Filament\Resources\DigitalItems\DigitalItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

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

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos')
                ->badge(fn () => static::getResource()::getEloquentQuery()->count()),
            'active' => Tab::make('Activos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('active', true))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('active', true)->count()),
            'inactive' => Tab::make('Inactivos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('active', false))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('active', false)->count()),
            'public' => Tab::make('Públicos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_public', true))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('is_public', true)->count()),
            'private' => Tab::make('Privados')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_public', false))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('is_public', false)->count()),
        ];
    }
}
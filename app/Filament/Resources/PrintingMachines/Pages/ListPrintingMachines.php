<?php

namespace App\Filament\Resources\PrintingMachines\Pages;

use App\Filament\Resources\PrintingMachines\PrintingMachineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPrintingMachines extends ListRecords
{
    protected static string $resource = PrintingMachineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todas')
                ->badge(fn () => static::getResource()::getEloquentQuery()->count()),
            'active' => Tab::make('Activas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('is_active', true)->count()),
            'inactive' => Tab::make('Inactivas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('is_active', false)->count()),
            'public' => Tab::make('Públicas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_public', true))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('is_public', true)->count()),
            'private' => Tab::make('Privadas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_public', false))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('is_public', false)->count()),
        ];
    }
}
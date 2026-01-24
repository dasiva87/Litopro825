<?php

namespace App\Filament\Resources\Products\ProductResource\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Producto')
                ->icon('heroicon-o-plus'),
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
<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos'),
            'draft' => Tab::make('Borradores')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'draft')->count()),
            'sent' => Tab::make('Enviados')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'sent'))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'sent')->count()),
            'approved' => Tab::make('Aprobados')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved'))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'approved')->count()),
            'in_production' => Tab::make('En Producción')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_production'))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'in_production')->count()),
        ];
    }
}
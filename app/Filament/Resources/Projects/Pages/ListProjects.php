<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos')
                ->badge(fn () => static::getResource()::getEloquentQuery()->count()),

            'active' => Tab::make('Activos')
                ->icon('heroicon-o-play')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    ProjectStatus::ACTIVE,
                    ProjectStatus::IN_PROGRESS,
                ]))
                ->badge(fn () => static::getResource()::getEloquentQuery()
                    ->whereIn('status', [ProjectStatus::ACTIVE, ProjectStatus::IN_PROGRESS])->count())
                ->badgeColor('info'),

            'completed' => Tab::make('Completados')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProjectStatus::COMPLETED))
                ->badge(fn () => static::getResource()::getEloquentQuery()
                    ->where('status', ProjectStatus::COMPLETED)->count())
                ->badgeColor('success'),

            'on_hold' => Tab::make('En Espera')
                ->icon('heroicon-o-pause')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProjectStatus::ON_HOLD))
                ->badge(fn () => static::getResource()::getEloquentQuery()
                    ->where('status', ProjectStatus::ON_HOLD)->count())
                ->badgeColor('warning'),
        ];
    }
}

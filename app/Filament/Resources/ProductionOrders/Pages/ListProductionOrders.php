<?php

namespace App\Filament\Resources\ProductionOrders\Pages;

use App\Filament\Resources\ProductionOrders\ProductionOrderResource;
use App\Enums\ProductionStatus;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProductionOrders extends ListRecords
{
    protected static string $resource = ProductionOrderResource::class;

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

            'draft' => Tab::make('Borradores')
                ->icon('heroicon-o-document')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductionStatus::DRAFT))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', ProductionStatus::DRAFT)->count())
                ->badgeColor('gray'),

            'sent' => Tab::make('Enviadas')
                ->icon('heroicon-o-paper-airplane')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductionStatus::SENT))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', ProductionStatus::SENT)->count())
                ->badgeColor('info'),

            'received' => Tab::make('Recibidas')
                ->icon('heroicon-o-inbox-arrow-down')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductionStatus::RECEIVED))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', ProductionStatus::RECEIVED)->count())
                ->badgeColor('primary'),

            'in_progress' => Tab::make('En Proceso')
                ->icon('heroicon-o-cog-6-tooth')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductionStatus::IN_PROGRESS))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', ProductionStatus::IN_PROGRESS)->count())
                ->badgeColor('warning'),

            'on_hold' => Tab::make('En Pausa')
                ->icon('heroicon-o-pause-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductionStatus::ON_HOLD))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', ProductionStatus::ON_HOLD)->count())
                ->badgeColor('gray'),

            'completed' => Tab::make('Finalizadas')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductionStatus::COMPLETED))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', ProductionStatus::COMPLETED)->count())
                ->badgeColor('success'),

            'cancelled' => Tab::make('Canceladas')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductionStatus::CANCELLED))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', ProductionStatus::CANCELLED)->count())
                ->badgeColor('danger'),
        ];
    }
}

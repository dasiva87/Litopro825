<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Enums\OrderStatus;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;

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
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::DRAFT))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', OrderStatus::DRAFT)->count())
                ->badgeColor('gray'),

            'sent' => Tab::make('Enviadas')
                ->icon('heroicon-o-paper-airplane')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::SENT))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', OrderStatus::SENT)->count())
                ->badgeColor('info'),

            'confirmed' => Tab::make('Confirmadas')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::CONFIRMED))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', OrderStatus::CONFIRMED)->count())
                ->badgeColor('warning'),

            'received' => Tab::make('Recibidas')
                ->icon('heroicon-o-archive-box')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::RECEIVED))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', OrderStatus::RECEIVED)->count())
                ->badgeColor('success'),

            'cancelled' => Tab::make('Canceladas')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::CANCELLED))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', OrderStatus::CANCELLED)->count())
                ->badgeColor('danger'),
        ];
    }
}

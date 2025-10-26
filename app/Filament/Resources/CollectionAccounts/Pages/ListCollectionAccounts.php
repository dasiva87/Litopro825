<?php

namespace App\Filament\Resources\CollectionAccounts\Pages;

use App\Filament\Resources\CollectionAccounts\CollectionAccountResource;
use App\Enums\CollectionAccountStatus;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCollectionAccounts extends ListRecords
{
    protected static string $resource = CollectionAccountResource::class;

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
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CollectionAccountStatus::DRAFT))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', CollectionAccountStatus::DRAFT)->count())
                ->badgeColor('gray'),

            'sent' => Tab::make('Enviadas')
                ->icon('heroicon-o-paper-airplane')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CollectionAccountStatus::SENT))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', CollectionAccountStatus::SENT)->count())
                ->badgeColor('warning'),

            'approved' => Tab::make('Aprobadas')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CollectionAccountStatus::APPROVED))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', CollectionAccountStatus::APPROVED)->count())
                ->badgeColor('info'),

            'paid' => Tab::make('Pagadas')
                ->icon('heroicon-o-banknotes')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CollectionAccountStatus::PAID))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', CollectionAccountStatus::PAID)->count())
                ->badgeColor('success'),

            'cancelled' => Tab::make('Canceladas')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CollectionAccountStatus::CANCELLED))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', CollectionAccountStatus::CANCELLED)->count())
                ->badgeColor('danger'),

            'pending' => Tab::make('Pendientes')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->whereIn('status', [
                        CollectionAccountStatus::DRAFT,
                        CollectionAccountStatus::SENT,
                        CollectionAccountStatus::APPROVED,
                    ])
                )
                ->badge(fn () => static::getResource()::getEloquentQuery()
                    ->whereIn('status', [
                        CollectionAccountStatus::DRAFT,
                        CollectionAccountStatus::SENT,
                        CollectionAccountStatus::APPROVED,
                    ])
                    ->count()
                )
                ->badgeColor('warning'),
        ];
    }
}

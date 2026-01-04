<?php

namespace App\Filament\Pages\CommercialRequests;

use App\Filament\Resources\CommercialRequestResource;
use App\Models\CommercialRequest;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCommercialRequests extends ListRecords
{
    protected static string $resource = CommercialRequestResource::class;

    protected static ?string $title = 'Solicitudes Comerciales';

    public function getTabs(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'todas' => Tab::make('Todas')
                ->icon('heroicon-o-queue-list')
                ->badge(fn () => CommercialRequest::query()
                    ->where(function ($query) use ($companyId) {
                        $query->where('requester_company_id', $companyId)
                            ->orWhere('target_company_id', $companyId);
                    })
                    ->count()),

            'pendientes_recibidas' => Tab::make('Pendientes Recibidas')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn ($query) => $query->forTarget($companyId)->pending())
                ->badge(fn () => CommercialRequest::forTarget($companyId)->pending()->count())
                ->badgeColor('warning'),

            'enviadas' => Tab::make('Enviadas')
                ->icon('heroicon-o-paper-airplane')
                ->modifyQueryUsing(fn ($query) => $query->fromRequester($companyId))
                ->badge(fn () => CommercialRequest::fromRequester($companyId)->count()),

            'recibidas' => Tab::make('Recibidas')
                ->icon('heroicon-o-inbox')
                ->modifyQueryUsing(fn ($query) => $query->forTarget($companyId))
                ->badge(fn () => CommercialRequest::forTarget($companyId)->count()),

            'aprobadas' => Tab::make('Aprobadas')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn ($query) => $query->approved())
                ->badge(fn () => CommercialRequest::query()
                    ->where(function ($query) use ($companyId) {
                        $query->where('requester_company_id', $companyId)
                            ->orWhere('target_company_id', $companyId);
                    })
                    ->approved()
                    ->count())
                ->badgeColor('success'),

            'rechazadas' => Tab::make('Rechazadas')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn ($query) => $query->rejected())
                ->badge(fn () => CommercialRequest::query()
                    ->where(function ($query) use ($companyId) {
                        $query->where('requester_company_id', $companyId)
                            ->orWhere('target_company_id', $companyId);
                    })
                    ->rejected()
                    ->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Widget de estadísticas (se puede implementar más tarde)
        ];
    }
}

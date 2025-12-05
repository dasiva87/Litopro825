<?php

namespace App\Filament\Pages\CommercialRequests;

use App\Filament\Resources\CommercialRequestResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Tabs\Tab;
use App\Models\CommercialRequest;
use Illuminate\Database\Eloquent\Builder;

class ListCommercialRequests extends ListRecords
{
    protected static string $resource = CommercialRequestResource::class;

    protected static ?string $title = 'Solicitudes Comerciales';

    protected function getTableQuery(): Builder
    {
        $companyId = auth()->user()->company_id;
        
        return CommercialRequest::query()
            ->with(['requesterCompany', 'targetCompany', 'requestedByUser', 'respondedByUser'])
            ->where(function ($query) use ($companyId) {
                $query->where('requester_company_id', $companyId)
                      ->orWhere('target_company_id', $companyId);
            });
    }

    protected function getTableTabs(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'todas' => Tab::make('Todas')
                ->badge(fn () => CommercialRequest::where('requester_company_id', $companyId)
                    ->orWhere('target_company_id', $companyId)->count()),

            'enviadas' => Tab::make('Enviadas')
                ->icon('heroicon-o-paper-airplane')
                ->modifyQueryUsing(fn ($query) => $query->fromRequester($companyId))
                ->badge(fn () => CommercialRequest::fromRequester($companyId)->count()),

            'recibidas' => Tab::make('Recibidas')
                ->icon('heroicon-o-inbox')
                ->modifyQueryUsing(fn ($query) => $query->forTarget($companyId))
                ->badge(fn () => CommercialRequest::forTarget($companyId)->count()),

            'pendientes' => Tab::make('Pendientes')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn ($query) => $query->forTarget($companyId)->pending())
                ->badge(fn () => CommercialRequest::forTarget($companyId)->pending()->count())
                ->badgeColor('warning'),

            'aprobadas' => Tab::make('Aprobadas')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn ($query) => $query->approved())
                ->badge(fn () => CommercialRequest::where('requester_company_id', $companyId)
                    ->orWhere('target_company_id', $companyId)->approved()->count())
                ->badgeColor('success'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Widget de estadísticas (se puede implementar más tarde)
        ];
    }
}
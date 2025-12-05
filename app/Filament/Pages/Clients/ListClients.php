<?php

namespace App\Filament\Pages\Clients;

use App\Filament\Resources\ClientResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected static ?string $title = 'Clientes';

    protected function getTableQuery(): Builder
    {
        return Contact::query()
            ->with(['linkedCompany'])
            ->customers()
            ->forCurrentTenant();
    }

    protected function getTableTabs(): array
    {
        return [
            'todos' => Tab::make('Todos')
                ->modifyQueryUsing(fn ($query) => $query),

            'locales' => Tab::make('Locales')
                ->icon('heroicon-o-map-pin')
                ->modifyQueryUsing(fn ($query) => $query->local())
                ->badge(fn () => Contact::customers()->local()->forCurrentTenant()->count()),

            'grafired' => Tab::make('Grafired')
                ->icon('heroicon-o-building-office-2')
                ->modifyQueryUsing(fn ($query) => $query->grafired())
                ->badge(fn () => Contact::customers()->grafired()->forCurrentTenant()->count()),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('add_local_client')
                ->label('Nuevo Cliente Local')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(fn () => route('filament.admin.resources.contacts.create', [
                    'type' => 'customer',
                    'is_local' => true
                ])),

            $this->getSearchGrafiredAction(),
        ];
    }

    protected function getSearchGrafiredAction(): Action
    {
        return Action::make('search_grafired_clients')
            ->label('Buscar en Grafired')
            ->icon('heroicon-o-magnifying-glass')
            ->color('success')
            ->modalWidth('7xl')
            ->modalHeading('🌐 Buscar Clientes en la Red Grafired')
            ->modalDescription('Encuentra y conecta con empresas de toda la red')
            ->modalContent(view('filament.modals.grafired-client-wrapper'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }
}
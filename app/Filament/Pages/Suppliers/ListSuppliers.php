<?php

namespace App\Filament\Pages\Suppliers;

use App\Filament\Resources\SupplierResource;
use App\Models\Company;
use App\Models\Contact;
use App\Services\CommercialRequestService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected static ?string $title = 'Proveedores';

    protected function getTableQuery(): Builder
    {
        return Contact::query()
            ->with(['linkedCompany'])
            ->suppliers()
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
                ->badge(fn () => Contact::suppliers()->local()->forCurrentTenant()->count()),

            'grafired' => Tab::make('Grafired')
                ->icon('heroicon-o-building-office-2')
                ->modifyQueryUsing(fn ($query) => $query->grafired())
                ->badge(fn () => Contact::suppliers()->grafired()->forCurrentTenant()->count()),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('add_local_supplier')
                ->label('Nuevo Proveedor Local')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(fn () => route('filament.admin.resources.contacts.create', [
                    'type' => 'supplier',
                    'is_local' => true
                ])),

            $this->getSearchGrafiredAction(),
        ];
    }

    protected function getSearchGrafiredAction(): Action
    {
        return Action::make('search_grafired_suppliers')
            ->label('Buscar en Grafired')
            ->icon('heroicon-o-magnifying-glass')
            ->color('success')
            ->modalWidth('7xl')
            ->modalHeading('🌐 Buscar Proveedores en la Red Grafired')
            ->modalDescription('Encuentra y conecta con empresas de toda la red')
            ->modalContent(view('filament.modals.grafired-livewire-wrapper'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }

    protected function getGrafiredCompanies()
    {
        return Company::query()
            ->where('is_public', true)
            ->where('is_active', true)
            ->where('id', '!=', auth()->user()->company_id)
            ->with(['city', 'state', 'country'])
            ->limit(20)
            ->get()
            ->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'tax_id' => $company->tax_id,
                    'logo' => $company->logo,
                    'company_type' => $company->company_type?->value,
                    'followers_count' => $company->followers_count,
                    'city' => $company->city ? ['name' => $company->city->name] : null,
                    'state' => $company->state ? ['name' => $company->state->name] : null,
                    'country' => $company->country ? ['name' => $company->country->name] : null,
                ];
            });
    }

    public function sendSupplierRequest($companyId, $message = null)
    {
        try {
            $company = Company::findOrFail($companyId);
            $service = app(CommercialRequestService::class);

            $service->sendRequest(
                targetCompany: $company,
                relationshipType: 'supplier',
                message: $message
            );

            Notification::make()
                ->success()
                ->title('Solicitud Enviada')
                ->body("Tu solicitud ha sido enviada a {$company->name}")
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->send();
        }
    }
}

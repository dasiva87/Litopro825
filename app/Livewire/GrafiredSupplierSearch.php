<?php

namespace App\Livewire;

use App\Models\Company;
use App\Services\CommercialRequestService;
use Filament\Notifications\Notification;
use Livewire\Component;

class GrafiredSupplierSearch extends Component
{
    public $search = '';
    public $company_type = [];
    public $country_id = null;
    public $requestMessage = '';

    public function render()
    {
        $companies = $this->searchCompanies();

        return view('livewire.grafired-supplier-search', [
            'companies' => $companies,
        ]);
    }

    protected function searchCompanies()
    {
        return Company::query()
            ->where('is_public', true)
            ->where('is_active', true)
            ->where('id', '!=', auth()->user()->company_id)
            ->when($this->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('tax_id', 'like', "%{$search}%");
                });
            })
            ->when($this->company_type, function ($q, $types) {
                $q->whereIn('company_type', $types);
            })
            ->when($this->country_id, fn ($q, $id) =>
                $q->where('country_id', $id)
            )
            ->with(['city', 'state', 'country'])
            ->limit(20)
            ->get();
    }

    public function requestSupplier(int $companyId, ?string $message = null): void
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

            // Reset message after sending
            $this->requestMessage = '';

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->send();
        }
    }
}

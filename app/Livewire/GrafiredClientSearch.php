<?php

namespace App\Livewire;

use App\Models\Company;
use App\Services\CommercialRequestService;
use Filament\Notifications\Notification;
use Livewire\Component;

class GrafiredClientSearch extends Component
{
    public $search = '';

    public function render()
    {
        $companies = $this->searchCompanies();

        return view('livewire.grafired-client-search', [
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
            ->with(['city', 'state', 'country'])
            ->limit(20)
            ->get();
    }

    public function requestClient(int $companyId, ?string $message = null): void
    {
        try {
            $company = Company::findOrFail($companyId);
            $service = app(CommercialRequestService::class);

            $service->sendRequest(
                targetCompany: $company,
                relationshipType: 'client',
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

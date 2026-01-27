<?php

namespace App\Livewire;

use App\Models\ClientRelationship;
use App\Models\CommercialRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Services\CommercialRequestService;
use Filament\Notifications\Notification;
use Livewire\Component;

class GrafiredClientSearch extends Component
{
    public $search = '';

    // Estados para confirmación de duplicado
    public $showDuplicateConfirmation = false;
    public $pendingCompanyId = null;
    public $pendingMessage = null;
    public $duplicateContactName = null;
    public $targetCompanyName = null;

    public function render()
    {
        $companies = $this->searchCompanies();
        $pendingRequestIds = $this->getPendingRequestIds();

        return view('livewire.grafired-client-search', [
            'companies' => $companies,
            'pendingRequestIds' => $pendingRequestIds,
        ]);
    }

    protected function searchCompanies()
    {
        $userCompanyId = auth()->user()->company_id;

        // IDs de clientes con los que ya tenemos relación
        $existingClientIds = ClientRelationship::where('supplier_company_id', $userCompanyId)
            ->where('is_active', true)
            ->pluck('client_company_id')
            ->toArray();

        return Company::query()
            ->where('is_public', true)
            ->where('is_active', true)
            ->where('id', '!=', $userCompanyId)
            // Excluir clientes con relación existente
            ->whereNotIn('id', $existingClientIds)
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

    /**
     * Obtener IDs de empresas con solicitudes pendientes
     */
    protected function getPendingRequestIds(): array
    {
        return CommercialRequest::where('requester_company_id', auth()->user()->company_id)
            ->where('relationship_type', 'client')
            ->where('status', 'pending')
            ->pluck('target_company_id')
            ->toArray();
    }

    public function requestClient(int $companyId, ?string $message = null): void
    {
        try {
            $company = Company::findOrFail($companyId);
            $service = app(CommercialRequestService::class);

            // Verificar si existe contacto local con mismo tax_id
            $duplicateContact = $service->checkDuplicateLocalContact(
                auth()->user()->company_id,
                $company->tax_id,
                'customer'
            );

            if ($duplicateContact && !$this->showDuplicateConfirmation) {
                // Mostrar modal de confirmación
                $this->pendingCompanyId = $companyId;
                $this->pendingMessage = $message;
                $this->duplicateContactName = $duplicateContact->name;
                $this->targetCompanyName = $company->name;
                $this->showDuplicateConfirmation = true;
                return;
            }

            // Enviar solicitud (ya confirmado o no hay duplicado)
            $service->sendRequest(
                targetCompany: $company,
                relationshipType: 'client',
                message: $message
            );

            $successMessage = "Tu solicitud ha sido enviada a {$company->name}";
            if ($duplicateContact) {
                $successMessage .= ". Cuando sea aprobada, se vinculará con tu contacto existente '{$duplicateContact->name}'.";
            }

            Notification::make()
                ->success()
                ->title('Solicitud Enviada')
                ->body($successMessage)
                ->send();

            // Reset states
            $this->resetDuplicateConfirmation();

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Confirmar y enviar solicitud cuando hay duplicado
     */
    public function confirmDuplicateRequest(): void
    {
        if ($this->pendingCompanyId) {
            // NO resetear showDuplicateConfirmation aquí
            // Se resetea en requestClient después de enviar exitosamente
            $this->requestClient($this->pendingCompanyId, $this->pendingMessage);
        }
    }

    /**
     * Cancelar solicitud con duplicado
     */
    public function cancelDuplicateRequest(): void
    {
        $this->resetDuplicateConfirmation();

        Notification::make()
            ->info()
            ->title('Solicitud Cancelada')
            ->body('No se envió la solicitud.')
            ->send();
    }

    /**
     * Resetear estados de confirmación de duplicado
     */
    protected function resetDuplicateConfirmation(): void
    {
        $this->showDuplicateConfirmation = false;
        $this->pendingCompanyId = null;
        $this->pendingMessage = null;
        $this->duplicateContactName = null;
        $this->targetCompanyName = null;
    }
}

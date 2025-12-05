<?php

namespace App\Services;

use App\Models\CommercialRequest;
use App\Models\Company;
use App\Models\User;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CommercialRequestService
{
    /**
     * Enviar solicitud de relación comercial
     */
    public function sendRequest(
        Company $targetCompany,
        string $relationshipType, // 'supplier' | 'client'
        ?string $message = null
    ): CommercialRequest {
        $user = auth()->user();

        // Validar que no exista solicitud pendiente
        $existing = CommercialRequest::where([
            'requester_company_id' => $user->company_id,
            'target_company_id' => $targetCompany->id,
            'relationship_type' => $relationshipType,
        ])
        ->whereIn('status', ['pending', 'approved'])
        ->first();

        if ($existing) {
            throw new \Exception('Ya existe una solicitud activa para esta empresa.');
        }

        // Validar que no sea la misma empresa
        if ($user->company_id === $targetCompany->id) {
            throw new \Exception('No puedes enviar una solicitud a tu propia empresa.');
        }

        // Crear solicitud
        $request = CommercialRequest::create([
            'requester_company_id' => $user->company_id,
            'target_company_id' => $targetCompany->id,
            'requested_by_user_id' => $user->id,
            'relationship_type' => $relationshipType,
            'status' => 'pending',
            'message' => $message,
        ]);

        // Notificar a la empresa objetivo (implementar después)
        // $this->notifyTargetCompany($request, $targetCompany);

        return $request;
    }

    /**
     * Aprobar solicitud y crear contacto vinculado
     */
    public function approveRequest(
        CommercialRequest $request,
        User $approver,
        ?string $responseMessage = null
    ): Contact {
        return DB::transaction(function () use ($request, $approver, $responseMessage) {
            // Actualizar solicitud
            $request->update([
                'status' => 'approved',
                'responded_by_user_id' => $approver->id,
                'responded_at' => now(),
                'response_message' => $responseMessage,
            ]);

            // Crear contacto vinculado para AMBAS empresas
            // Convertir relationship_type de commercial_requests a contact.type
            // 'supplier' en request → 'supplier' en contact
            // 'client' en request → 'customer' en contact
            $contactForTarget = $this->createLinkedContact(
                companyId: $request->target_company_id,
                linkedCompanyId: $request->requester_company_id,
                type: $request->relationship_type === 'supplier' ? 'customer' : 'supplier'
            );

            $contactForRequester = $this->createLinkedContact(
                companyId: $request->requester_company_id,
                linkedCompanyId: $request->target_company_id,
                type: $request->relationship_type === 'supplier' ? 'supplier' : 'customer'
            );

            return $contactForRequester;
        });
    }

    /**
     * Crear contacto vinculado a empresa Grafired
     */
    protected function createLinkedContact(
        int $companyId,
        int $linkedCompanyId,
        string $type
    ): Contact {
        $linkedCompany = Company::find($linkedCompanyId);

        // Verificar si ya existe el contacto
        $existing = Contact::where('company_id', $companyId)
            ->where('linked_company_id', $linkedCompanyId)
            ->first();

        if ($existing) {
            return $existing;
        }

        return Contact::create([
            'company_id' => $companyId,
            'linked_company_id' => $linkedCompanyId,
            'type' => $type,
            'is_local' => false, // Es de Grafired
            'name' => $linkedCompany->name,
            'email' => $linkedCompany->email,
            'phone' => $linkedCompany->phone,
            'address' => $linkedCompany->address,
            'city_id' => $linkedCompany->city_id,
            'state_id' => $linkedCompany->state_id,
            'country_id' => $linkedCompany->country_id,
            'tax_id' => $linkedCompany->tax_id,
            'website' => $linkedCompany->website,
            'is_active' => true,
        ]);
    }

    /**
     * Rechazar solicitud
     */
    public function rejectRequest(
        CommercialRequest $request,
        User $rejecter,
        string $reason
    ): CommercialRequest {
        $request->update([
            'status' => 'rejected',
            'responded_by_user_id' => $rejecter->id,
            'responded_at' => now(),
            'response_message' => $reason,
        ]);

        return $request;
    }
}

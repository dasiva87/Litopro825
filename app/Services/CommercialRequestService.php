<?php

namespace App\Services;

use App\Models\CommercialRequest;
use App\Models\Company;
use App\Models\User;
use App\Models\Contact;
use App\Models\SupplierRelationship;
use App\Models\ClientRelationship;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CommercialRequestService
{
    /**
     * Verificar si existe un contacto local con el mismo tax_id
     * Retorna el contacto local si existe, null si no
     */
    public function checkDuplicateLocalContact(
        int $companyId,
        ?string $taxId,
        string $type // 'supplier' | 'customer'
    ): ?Contact {
        if (empty($taxId)) {
            return null;
        }

        return Contact::where('company_id', $companyId)
            ->where('tax_id', $taxId)
            ->where('is_local', true)
            ->whereIn('type', [$type, 'both'])
            ->first();
    }

    /**
     * Enviar solicitud de relación comercial
     */
    public function sendRequest(
        Company $targetCompany,
        string $relationshipType, // 'supplier' | 'client'
        ?string $message = null
    ): CommercialRequest {
        $user = auth()->user();

        // Validar que no sea la misma empresa
        if ($user->company_id === $targetCompany->id) {
            throw new \Exception('No puedes enviar una solicitud a tu propia empresa.');
        }

        // Buscar solicitud existente (cualquier estado)
        $existing = CommercialRequest::where([
            'requester_company_id' => $user->company_id,
            'target_company_id' => $targetCompany->id,
            'relationship_type' => $relationshipType,
        ])->first();

        if ($existing) {
            // Si está pendiente o aprobada, no permitir nueva solicitud
            if (in_array($existing->status, ['pending', 'approved'])) {
                throw new \Exception('Ya existe una solicitud activa para esta empresa.');
            }

            // Si fue rechazada, reactivar la solicitud
            if ($existing->status === 'rejected') {
                $existing->update([
                    'status' => 'pending',
                    'message' => $message,
                    'requested_by_user_id' => $user->id,
                    'response_message' => null,
                    'responded_at' => null,
                    'responded_by_user_id' => null,
                ]);

                return $existing;
            }
        }

        // Crear nueva solicitud
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

            // Crear relación formal (SupplierRelationship o ClientRelationship)
            if ($request->relationship_type === 'supplier') {
                // Requester solicita que Target sea su proveedor
                // → SupplierRelationship: client=Requester, supplier=Target
                $this->createSupplierRelationship(
                    clientCompanyId: $request->requester_company_id,
                    supplierCompanyId: $request->target_company_id,
                    approver: $approver
                );
            } else {
                // Requester solicita que Target sea su cliente
                // → ClientRelationship: supplier=Requester, client=Target
                $this->createClientRelationship(
                    supplierCompanyId: $request->requester_company_id,
                    clientCompanyId: $request->target_company_id,
                    approver: $approver
                );
            }

            return $contactForRequester;
        });
    }

    /**
     * Crear contacto vinculado a empresa Grafired
     * Si existe un contacto local con el mismo tax_id, lo convierte en enlazado
     */
    protected function createLinkedContact(
        int $companyId,
        int $linkedCompanyId,
        string $type
    ): Contact {
        $linkedCompany = Company::find($linkedCompanyId);

        // Verificar si ya existe el contacto enlazado
        $existingLinked = Contact::where('company_id', $companyId)
            ->where('linked_company_id', $linkedCompanyId)
            ->first();

        if ($existingLinked) {
            return $existingLinked;
        }

        // NUEVO: Verificar si existe contacto LOCAL con mismo tax_id
        $existingLocal = $this->checkDuplicateLocalContact(
            $companyId,
            $linkedCompany->tax_id,
            $type
        );

        if ($existingLocal) {
            // CONVERTIR el contacto local en enlazado (no crear nuevo)
            $existingLocal->update([
                'is_local' => false,
                'linked_company_id' => $linkedCompanyId,
                // Sincronizar datos oficiales del gremio
                'name' => $linkedCompany->name,
                'email' => $linkedCompany->email,
                'phone' => $linkedCompany->phone,
                'address' => $linkedCompany->address,
                'city_id' => $linkedCompany->city_id,
                'state_id' => $linkedCompany->state_id,
                'country_id' => $linkedCompany->country_id,
                'tax_id' => $linkedCompany->tax_id,
                'website' => $linkedCompany->website,
                // Mantener datos comerciales del usuario (credit_limit, payment_terms, etc.)
            ]);

            return $existingLocal;
        }

        // Si no existe duplicado, crear nuevo contacto enlazado
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
     * Crear relación de proveedor
     */
    protected function createSupplierRelationship(
        int $clientCompanyId,
        int $supplierCompanyId,
        User $approver
    ): SupplierRelationship {
        // Verificar si ya existe
        $existing = SupplierRelationship::where([
            'client_company_id' => $clientCompanyId,
            'supplier_company_id' => $supplierCompanyId,
        ])->first();

        if ($existing) {
            return $existing;
        }

        return SupplierRelationship::create([
            'client_company_id' => $clientCompanyId,
            'supplier_company_id' => $supplierCompanyId,
            'approved_by_user_id' => $approver->id,
            'approved_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Crear relación de cliente
     */
    protected function createClientRelationship(
        int $supplierCompanyId,
        int $clientCompanyId,
        User $approver
    ): ClientRelationship {
        // Verificar si ya existe
        $existing = ClientRelationship::where([
            'supplier_company_id' => $supplierCompanyId,
            'client_company_id' => $clientCompanyId,
        ])->first();

        if ($existing) {
            return $existing;
        }

        return ClientRelationship::create([
            'supplier_company_id' => $supplierCompanyId,
            'client_company_id' => $clientCompanyId,
            'approved_by_user_id' => $approver->id,
            'approved_at' => now(),
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

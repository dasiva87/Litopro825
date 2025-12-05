<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Contact;
use App\Models\SupplierRelationship;
use App\Models\SupplierRequest;
use App\Models\CommercialRequest;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PASO 1: Marcar todos los contactos existentes como locales
        DB::table('contacts')
            ->whereNull('is_local')
            ->update(['is_local' => true]);

        // PASO 2: Migrar SupplierRequest a CommercialRequest
        $supplierRequests = DB::table('supplier_requests')->get();
        
        foreach ($supplierRequests as $request) {
            // Verificar que no exista ya la solicitud comercial
            $existingRequest = DB::table('commercial_requests')
                ->where('requester_company_id', $request->requester_company_id)
                ->where('target_company_id', $request->supplier_company_id)
                ->where('relationship_type', 'supplier')
                ->first();

            if (!$existingRequest) {
                DB::table('commercial_requests')->insert([
                    'requester_company_id' => $request->requester_company_id,
                    'target_company_id' => $request->supplier_company_id,
                    'requested_by_user_id' => $request->requested_by_user_id,
                    'relationship_type' => 'supplier',
                    'status' => $request->status,
                    'message' => $request->message,
                    'response_message' => $request->response_message,
                    'responded_at' => $request->responded_at,
                    'responded_by_user_id' => $request->responded_by_user_id,
                    'created_at' => $request->created_at,
                    'updated_at' => $request->updated_at,
                ]);
            }
        }

        // PASO 3: Crear Contacts de Grafired desde SupplierRelationships
        $relationships = DB::table('supplier_relationships')
            ->join('companies as supplier', 'supplier_relationships.supplier_company_id', '=', 'supplier.id')
            ->select('supplier_relationships.*', 'supplier.name', 'supplier.email', 'supplier.phone', 'supplier.address')
            ->get();

        foreach ($relationships as $relationship) {
            // Crear Contact del proveedor en la empresa cliente
            $existingContact = DB::table('contacts')
                ->where('company_id', $relationship->client_company_id)
                ->where('linked_company_id', $relationship->supplier_company_id)
                ->first();

            if (!$existingContact) {
                DB::table('contacts')->insert([
                    'company_id' => $relationship->client_company_id,
                    'type' => 'supplier',
                    'name' => $relationship->name,
                    'email' => $relationship->email,
                    'phone' => $relationship->phone,
                    'address' => $relationship->address,
                    'is_local' => false,
                    'linked_company_id' => $relationship->supplier_company_id,
                    'is_active' => $relationship->is_active,
                    'notes' => 'Migrado desde SupplierRelationship - ' . now()->format('Y-m-d'),
                    'created_at' => $relationship->created_at,
                    'updated_at' => now(),
                ]);
            }
        }

        // PASO 4: Crear ClientRelationships inversas (para papelerías que vean sus clientes)
        foreach ($relationships as $relationship) {
            $existingClientRel = DB::table('client_relationships')
                ->where('supplier_company_id', $relationship->supplier_company_id)
                ->where('client_company_id', $relationship->client_company_id)
                ->first();

            if (!$existingClientRel) {
                DB::table('client_relationships')->insert([
                    'supplier_company_id' => $relationship->supplier_company_id,
                    'client_company_id' => $relationship->client_company_id,
                    'approved_by_user_id' => $relationship->approved_by_user_id,
                    'approved_at' => $relationship->approved_at,
                    'is_active' => $relationship->is_active,
                    'notes' => 'Creada automáticamente desde migración - ' . now()->format('Y-m-d'),
                    'created_at' => $relationship->created_at,
                    'updated_at' => now(),
                ]);

                // Crear Contact del cliente en la empresa proveedora
                $clientCompany = DB::table('companies')->where('id', $relationship->client_company_id)->first();
                if ($clientCompany) {
                    $existingClientContact = DB::table('contacts')
                        ->where('company_id', $relationship->supplier_company_id)
                        ->where('linked_company_id', $relationship->client_company_id)
                        ->first();

                    if (!$existingClientContact) {
                        DB::table('contacts')->insert([
                            'company_id' => $relationship->supplier_company_id,
                            'type' => 'customer',
                            'name' => $clientCompany->name,
                            'email' => $clientCompany->email,
                            'phone' => $clientCompany->phone,
                            'address' => $clientCompany->address,
                            'is_local' => false,
                            'linked_company_id' => $relationship->client_company_id,
                            'is_active' => $relationship->is_active,
                            'notes' => 'Cliente creado desde migración - ' . now()->format('Y-m-d'),
                            'created_at' => $relationship->created_at,
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // PASO 5: Log de migración (simplificado)
        echo "✅ Migración completada:\n";
        echo "   - Contactos migrados: " . DB::table('contacts')->where('is_local', true)->count() . "\n";
        echo "   - Solicitudes migradas: " . $supplierRequests->count() . "\n";
        echo "   - Relaciones cliente creadas: " . DB::table('client_relationships')->count() . "\n";
        echo "   - Contactos Grafired creados: " . DB::table('contacts')->where('is_local', false)->count() . "\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir migración: eliminar datos creados automáticamente
        DB::table('contacts')->where('is_local', false)->delete();
        DB::table('client_relationships')->delete();
        DB::table('commercial_requests')
            ->where('created_at', '>=', now()->subMinutes(5)) // Solo los recién migrados
            ->delete();

        // Restaurar is_local a null
        DB::table('contacts')->update(['is_local' => null, 'linked_company_id' => null]);
    }
};
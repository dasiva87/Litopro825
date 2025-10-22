<?php

namespace Database\Seeders;

use App\Enums\CollectionAccountStatus;
use App\Models\CollectionAccount;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class CollectionAccountSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener empresas y documentos aprobados para testing
        $companies = Company::where('company_type', 'litografia')->take(2)->get();

        if ($companies->isEmpty()) {
            $this->command->info('No hay litografías disponibles. Creando datos de ejemplo...');
            return;
        }

        foreach ($companies as $company) {
            // Obtener clientes (otras empresas)
            $clients = Company::where('id', '!=', $company->id)->take(3)->get();

            if ($clients->isEmpty()) {
                continue;
            }

            // Crear cuentas de cobro en diferentes estados
            foreach ($clients as $index => $client) {
                // Estado según índice para variedad
                $status = match($index % 5) {
                    0 => CollectionAccountStatus::DRAFT,
                    1 => CollectionAccountStatus::SENT,
                    2 => CollectionAccountStatus::APPROVED,
                    3 => CollectionAccountStatus::PAID,
                    default => CollectionAccountStatus::SENT,
                };

                $account = CollectionAccount::factory()
                    ->state([
                        'company_id' => $company->id,
                        'client_company_id' => $client->id,
                        'status' => $status,
                        'created_by' => User::where('company_id', $company->id)->first()?->id ?? 1,
                    ])
                    ->create();

                // Buscar items de cotizaciones aprobadas para este cliente
                $documents = Document::where('company_id', $company->id)
                    ->where('contact_id', function($query) use ($client) {
                        $query->select('id')
                            ->from('contacts')
                            ->where('company_id', $client->id)
                            ->limit(1);
                    })
                    ->where('status', 'approved')
                    ->take(2)
                    ->get();

                // Si no hay documentos, buscar cualquier documento aprobado de la empresa
                if ($documents->isEmpty()) {
                    $documents = Document::where('company_id', $company->id)
                        ->where('status', 'approved')
                        ->take(2)
                        ->get();
                }

                // Agregar items a la cuenta de cobro
                foreach ($documents as $document) {
                    $items = DocumentItem::where('document_id', $document->id)
                        ->take(rand(2, 4))
                        ->get();

                    foreach ($items as $item) {
                        $account->documentItems()->attach($item->id, [
                            'quantity_ordered' => $item->quantity ?? 1,
                            'unit_price' => $item->unit_price ?? 0,
                            'total_price' => $item->total_price ?? 0,
                            'status' => 'pending',
                        ]);
                    }
                }

                $account->recalculateTotal();

                $this->command->info("Cuenta de cobro {$account->account_number} creada para {$client->name}");
            }
        }

        $this->command->info('Seeders de Cuentas de Cobro completados.');
    }
}

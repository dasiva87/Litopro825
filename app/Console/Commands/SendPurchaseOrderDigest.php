<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Notifications\PurchaseOrderDigest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendPurchaseOrderDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchase-orders:send-digest {--company= : Send digest for specific company ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily digest of purchase orders to company users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Enviando resumen diario de órdenes de pedido...');

        $companiesQuery = Company::query();

        if ($companyId = $this->option('company')) {
            $companiesQuery->where('id', $companyId);
        }

        $companies = $companiesQuery->get();

        foreach ($companies as $company) {
            $this->processCompanyDigest($company);
        }

        $this->info('✅ Resumen diario enviado exitosamente');
    }

    private function processCompanyDigest(Company $company)
    {
        // Obtener órdenes del último día
        $yesterday = now()->subDay();
        $today = now();

        $orders = PurchaseOrder::where('company_id', $company->id)
            ->whereBetween('created_at', [$yesterday, $today])
            ->with(['supplierCompany', 'items'])
            ->get();

        // Obtener órdenes pendientes
        $pendingOrders = PurchaseOrder::where('company_id', $company->id)
            ->whereIn('status', ['draft', 'sent', 'confirmed', 'partially_received'])
            ->with(['supplierCompany', 'items'])
            ->get();

        // Solo enviar si hay datos relevantes
        if ($orders->isNotEmpty() || $pendingOrders->count() > 5) {
            $users = User::where('company_id', $company->id)
                ->whereNotNull('email_verified_at')
                ->get();

            if ($users->isNotEmpty()) {
                Notification::send($users, new PurchaseOrderDigest($company, $orders, $pendingOrders));
                $this->line("📧 Resumen enviado a {$users->count()} usuarios de {$company->name}");
            }
        } else {
            $this->line("⏭️  Saltando {$company->name} - Sin actividad relevante");
        }
    }
}

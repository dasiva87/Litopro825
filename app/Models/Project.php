<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Virtual model to group documents by reference (project code)
 * This is not a database table, but an aggregator class
 */
class Project
{
    public string $code;
    public ?string $clientName = null;
    public ?int $clientCompanyId = null;
    public ?int $contactId = null;
    public ?string $status = null;
    public ?\Carbon\Carbon $startDate = null;
    public ?\Carbon\Carbon $lastActivityDate = null;
    public float $totalAmount = 0;
    public int $documentsCount = 0;
    public int $purchaseOrdersCount = 0;
    public int $productionOrdersCount = 0;
    public int $collectionAccountsCount = 0;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    /**
     * Get all projects for current tenant
     */
    public static function all(): Collection
    {
        $companyId = auth()->user()->company_id ?? config('app.current_tenant_id');

        // Get all unique references from documents
        $projects = collect();

        // Get documents with references
        $documents = Document::where('company_id', $companyId)
            ->whereNotNull('reference')
            ->where('reference', '!=', '')
            ->select('reference', DB::raw('MIN(date) as start_date'), DB::raw('MAX(updated_at) as last_activity'))
            ->groupBy('reference')
            ->get();

        foreach ($documents as $doc) {
            $project = new self($doc->reference);
            $project->loadData();
            $projects->push($project);
        }

        return $projects->sortByDesc('lastActivityDate');
    }

    /**
     * Find a project by code
     */
    public static function find(string $code): ?self
    {
        $companyId = auth()->user()->company_id ?? config('app.current_tenant_id');

        $exists = Document::where('company_id', $companyId)
            ->where('reference', $code)
            ->exists();

        if (!$exists) {
            return null;
        }

        $project = new self($code);
        $project->loadData();

        return $project;
    }

    /**
     * Load all data for this project
     */
    public function loadData(): void
    {
        $companyId = auth()->user()->company_id ?? config('app.current_tenant_id');

        // Get all documents
        $documents = $this->getDocuments();
        $this->documentsCount = $documents->count();

        if ($documents->isNotEmpty()) {
            // Get dates
            $this->startDate = $documents->min('date');
            $this->lastActivityDate = $documents->max('updated_at');

            // Get client from first document
            $firstDoc = $documents->first();
            if ($firstDoc->contact_id) {
                $this->contactId = $firstDoc->contact_id;
                $this->clientName = $firstDoc->contact?->name ?? 'Sin cliente';
            } elseif ($firstDoc->client_company_id) {
                $this->clientCompanyId = $firstDoc->client_company_id;
                $this->clientName = $firstDoc->clientCompany?->name ?? 'Sin cliente';
            }

            // Calculate total from documents
            $this->totalAmount = $documents->sum('total');

            // Determine status
            $this->status = $this->determineStatus($documents);
        }

        // Get purchase orders count
        $this->purchaseOrdersCount = PurchaseOrder::where('company_id', $companyId)
            ->whereHas('documentItems.document', function ($query) {
                $query->where('reference', $this->code);
            })
            ->count();

        // Get production orders count
        $this->productionOrdersCount = ProductionOrder::where('company_id', $companyId)
            ->whereHas('documentItems.document', function ($query) {
                $query->where('reference', $this->code);
            })
            ->count();

        // Get collection accounts count
        $this->collectionAccountsCount = CollectionAccount::where('company_id', $companyId)
            ->whereHas('documentItems.document', function ($query) {
                $query->where('reference', $this->code);
            })
            ->count();
    }

    /**
     * Get all documents for this project
     */
    public function getDocuments(): Collection
    {
        $companyId = auth()->user()->company_id ?? config('app.current_tenant_id');

        return Document::where('company_id', $companyId)
            ->where('reference', $this->code)
            ->with(['documentType', 'contact', 'clientCompany'])
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Get all purchase orders for this project
     */
    public function getPurchaseOrders(): Collection
    {
        $companyId = auth()->user()->company_id ?? config('app.current_tenant_id');

        return PurchaseOrder::where('company_id', $companyId)
            ->whereHas('documentItems.document', function ($query) {
                $query->where('reference', $this->code);
            })
            ->with(['supplier', 'supplierCompany'])
            ->orderBy('order_date', 'desc')
            ->get();
    }

    /**
     * Get all production orders for this project
     */
    public function getProductionOrders(): Collection
    {
        $companyId = auth()->user()->company_id ?? config('app.current_tenant_id');

        return ProductionOrder::where('company_id', $companyId)
            ->whereHas('documentItems.document', function ($query) {
                $query->where('reference', $this->code);
            })
            ->with(['supplier', 'supplierCompany', 'operator'])
            ->orderBy('scheduled_date', 'desc')
            ->get();
    }

    /**
     * Get all collection accounts for this project
     */
    public function getCollectionAccounts(): Collection
    {
        $companyId = auth()->user()->company_id ?? config('app.current_tenant_id');

        return CollectionAccount::where('company_id', $companyId)
            ->whereHas('documentItems.document', function ($query) {
                $query->where('reference', $this->code);
            })
            ->with(['contact', 'clientCompany'])
            ->orderBy('issue_date', 'desc')
            ->get();
    }

    /**
     * Get timeline of all activities
     */
    public function getTimeline(): Collection
    {
        $timeline = collect();

        // Add documents
        foreach ($this->getDocuments() as $doc) {
            $timeline->push([
                'date' => $doc->date,
                'type' => 'document',
                'icon' => $this->getDocumentIcon($doc->documentType->code),
                'title' => $doc->documentType->name . ' ' . $doc->document_number,
                'status' => $doc->status,
                'description' => $doc->client_name,
                'amount' => $doc->total,
                'url' => route('filament.admin.resources.documents.edit', $doc),
            ]);
        }

        // Add purchase orders
        foreach ($this->getPurchaseOrders() as $order) {
            $timeline->push([
                'date' => $order->order_date,
                'type' => 'purchase_order',
                'icon' => '📋',
                'title' => 'Orden de Pedido ' . $order->order_number,
                'status' => $order->status->value,
                'description' => 'Proveedor: ' . $order->supplier_name,
                'amount' => $order->total_amount,
                'url' => route('filament.admin.resources.purchase-orders.edit', $order),
            ]);
        }

        // Add production orders
        foreach ($this->getProductionOrders() as $order) {
            $timeline->push([
                'date' => $order->scheduled_date ?? $order->created_at,
                'type' => 'production_order',
                'icon' => '🏭',
                'title' => 'Orden de Producción ' . $order->production_number,
                'status' => $order->status->value,
                'description' => 'Proveedor: ' . $order->supplier_name,
                'amount' => null,
                'url' => route('filament.admin.resources.production-orders.view', $order),
            ]);
        }

        // Add collection accounts
        foreach ($this->getCollectionAccounts() as $account) {
            $timeline->push([
                'date' => $account->issue_date,
                'type' => 'collection_account',
                'icon' => '💰',
                'title' => 'Cuenta de Cobro ' . $account->account_number,
                'status' => $account->status->value,
                'description' => 'Cliente: ' . ($account->contact?->name ?? $account->clientCompany?->name ?? 'N/A'),
                'amount' => $account->total_amount,
                'url' => route('filament.admin.resources.collection-accounts.edit', $account),
            ]);
        }

        return $timeline->sortByDesc('date');
    }

    /**
     * Determine overall project status
     */
    private function determineStatus(Collection $documents): string
    {
        $statuses = $documents->pluck('status')->unique();

        if ($statuses->contains('cancelled')) {
            return 'cancelled';
        }

        if ($statuses->contains('completed')) {
            return 'completed';
        }

        if ($statuses->contains('in_production')) {
            return 'in_production';
        }

        if ($statuses->contains('approved')) {
            return 'approved';
        }

        if ($statuses->contains('sent')) {
            return 'sent';
        }

        return 'draft';
    }

    /**
     * Get icon for document type
     */
    private function getDocumentIcon(string $typeCode): string
    {
        return match ($typeCode) {
            'QUOTE' => '📄',
            'ORDER' => '📋',
            'INVOICE' => '🧾',
            default => '📄',
        };
    }

    /**
     * Get status color
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'in_production' => 'warning',
            'approved' => 'info',
            'sent' => 'primary',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get status label in Spanish
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Borrador',
            'sent' => 'Enviado',
            'approved' => 'Aprobado',
            'in_production' => 'En Producción',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
            default => 'Desconocido',
        };
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentage(): int
    {
        $total = $this->documentsCount + $this->purchaseOrdersCount +
                 $this->productionOrdersCount + $this->collectionAccountsCount;

        if ($total === 0) {
            return 0;
        }

        $completed = 0;

        // Check if has collection account (final step)
        if ($this->collectionAccountsCount > 0) {
            $accounts = $this->getCollectionAccounts();
            if ($accounts->where('status', 'paid')->count() > 0) {
                return 100;
            }
            $completed = 75;
        } elseif ($this->productionOrdersCount > 0) {
            $completed = 50;
        } elseif ($this->purchaseOrdersCount > 0) {
            $completed = 25;
        }

        return $completed;
    }
}

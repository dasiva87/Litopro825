<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\PurchaseOrder;
use App\Models\ProductionOrder;
use App\Models\CollectionAccount;
use Filament\Facades\Filament;

class ProjectDetailController extends Controller
{
    public function show(string $code)
    {
        // Verificar que el proyecto existe
        $companyId = auth()->user()->company_id ?? config('app.current_tenant_id');
        $exists = Document::where('company_id', $companyId)
            ->where('reference', $code)
            ->exists();

        if (!$exists) {
            abort(404, 'Proyecto no encontrado');
        }

        // Obtener datos
        $documents = Document::where('company_id', $companyId)
            ->where('reference', $code)
            ->with(['documentType', 'contact', 'clientCompany'])
            ->orderBy('date', 'desc')
            ->get();

        $documentIds = Document::where('company_id', $companyId)
            ->where('reference', $code)
            ->pluck('id');

        $purchaseOrders = PurchaseOrder::where('company_id', $companyId)
            ->whereHas('documentItems', function ($query) use ($documentIds) {
                $query->whereIn('document_id', $documentIds);
            })
            ->with(['supplier', 'supplierCompany'])
            ->orderBy('order_date', 'desc')
            ->get();

        $productionOrders = ProductionOrder::where('company_id', $companyId)
            ->whereHas('documentItems', function ($query) use ($documentIds) {
                $query->whereIn('document_id', $documentIds);
            })
            ->with(['supplier', 'supplierCompany', 'operator'])
            ->orderBy('created_at', 'desc')
            ->get();

        $collectionAccounts = CollectionAccount::where('company_id', $companyId)
            ->whereHas('documentItems', function ($query) use ($documentIds) {
                $query->whereIn('document_id', $documentIds);
            })
            ->with(['contact', 'clientCompany'])
            ->orderBy('issue_date', 'desc')
            ->get();

        return view('filament.pages.project-detail-simple', compact(
            'code',
            'documents',
            'purchaseOrders',
            'productionOrders',
            'collectionAccounts'
        ));
    }
}

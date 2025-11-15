<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Services\ProductionOrderPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductionOrderPdfController extends Controller
{
    protected ProductionOrderPdfService $pdfService;

    public function __construct(ProductionOrderPdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function show(ProductionOrder $productionOrder)
    {
        // Verificar acceso multi-tenant
        $currentCompanyId = auth()->user()->company_id ?? config('app.current_tenant_id');

        if ($productionOrder->company_id !== $currentCompanyId) {
            abort(403, 'Acceso denegado a esta orden de producción.');
        }

        return $this->pdfService->streamPdf($productionOrder);
    }

    public function download(ProductionOrder $productionOrder)
    {
        // Verificar acceso multi-tenant
        $currentCompanyId = auth()->user()->company_id ?? config('app.current_tenant_id');

        if ($productionOrder->company_id !== $currentCompanyId) {
            abort(403, 'Acceso denegado a esta orden de producción.');
        }

        return $this->pdfService->downloadPdf($productionOrder);
    }
}

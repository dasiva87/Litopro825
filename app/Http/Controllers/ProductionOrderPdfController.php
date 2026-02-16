<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductionOrderPdfController extends Controller
{
    public function show(ProductionOrder $productionOrder)
    {
        // Usar Policy para verificar acceso multi-tenant
        $this->authorize('view', $productionOrder);

        // Cargar todas las relaciones necesarias para el PDF
        $productionOrder->load([
            'company',
            'supplier',
            'supplierCompany',
            'operator',
            'productionProcesses.documentItem.itemable',
            'productionProcesses.documentItem.document',
        ]);

        // Generar PDF con DomPDF en tamaño carta
        $pdf = Pdf::loadView('production-orders.pdf', compact('productionOrder'))
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'defaultPaperSize' => 'letter',
            ]);

        // Nombre del archivo
        $filename = "orden-produccion-{$productionOrder->production_number}.pdf";

        return $pdf->stream($filename);
    }

    public function download(ProductionOrder $productionOrder)
    {
        // Usar Policy para verificar acceso multi-tenant
        $this->authorize('view', $productionOrder);

        // Cargar todas las relaciones necesarias para el PDF
        $productionOrder->load([
            'company',
            'supplier',
            'supplierCompany',
            'operator',
            'productionProcesses.documentItem.itemable',
            'productionProcesses.documentItem.document',
        ]);

        // Generar PDF con DomPDF en tamaño carta
        $pdf = Pdf::loadView('production-orders.pdf', compact('productionOrder'))
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'defaultPaperSize' => 'letter',
            ]);

        // Nombre del archivo
        $filename = "orden-produccion-{$productionOrder->production_number}.pdf";

        return $pdf->download($filename);
    }
}

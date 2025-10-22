<?php

namespace App\Http\Controllers;

use App\Models\CollectionAccount;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CollectionAccountPdfController extends Controller
{
    public function show(CollectionAccount $collectionAccount)
    {
        // Verificar acceso multi-tenant
        if ($collectionAccount->company_id !== config('app.current_tenant_id')) {
            abort(403, 'Acceso denegado a esta cuenta de cobro.');
        }

        // Cargar todas las relaciones necesarias para el PDF
        $collectionAccount->load([
            'company',
            'clientCompany',
            'documentItems.itemable',
            'documentItems.document',
            'createdBy'
        ]);

        // Generar PDF con DomPDF en tamaño carta
        $pdf = Pdf::loadView('collection-accounts.pdf', compact('collectionAccount'))
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'defaultPaperSize' => 'letter',
            ]);

        // Nombre del archivo
        $filename = $collectionAccount->account_number . '.pdf';

        return $pdf->stream($filename);
    }

    public function download(CollectionAccount $collectionAccount)
    {
        // Verificar acceso multi-tenant
        if ($collectionAccount->company_id !== config('app.current_tenant_id')) {
            abort(403, 'Acceso denegado a esta cuenta de cobro.');
        }

        // Cargar todas las relaciones necesarias para el PDF
        $collectionAccount->load([
            'company',
            'clientCompany',
            'documentItems.itemable',
            'documentItems.document',
            'createdBy'
        ]);

        // Generar PDF con DomPDF en tamaño carta
        $pdf = Pdf::loadView('collection-accounts.pdf', compact('collectionAccount'))
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'defaultPaperSize' => 'letter',
            ]);

        // Nombre del archivo
        $filename = $collectionAccount->account_number . '.pdf';

        return $pdf->download($filename);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentPdfController extends Controller
{
    public function show(Document $document)
    {
        // Verificar que el usuario tenga acceso al documento (multi-tenant)
        if (auth()->check() && $document->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        // Cargar todas las relaciones necesarias para el PDF
        $document->load([
            'company',
            'contact', 
            'documentType',
            'items.itemable'
        ]);
        
        // Generar PDF con DomPDF en tamaño carta
        $pdf = Pdf::loadView('documents.pdf', compact('document'))
            ->setPaper('letter', 'portrait') // Tamaño carta vertical
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150, // Mejor calidad
                'defaultPaperSize' => 'letter',
            ]);

        // Nombre del archivo
        $filename = $document->document_number . '.pdf';
        
        return $pdf->stream($filename);
    }

    public function download(Document $document)
    {
        // Verificar que el usuario tenga acceso al documento (multi-tenant)
        if (auth()->check() && $document->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        // Cargar todas las relaciones necesarias para el PDF
        $document->load([
            'company',
            'contact', 
            'documentType',
            'items.itemable'
        ]);
        
        // Generar PDF con DomPDF en tamaño carta
        $pdf = Pdf::loadView('documents.pdf', compact('document'))
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'defaultPaperSize' => 'letter',
            ]);

        // Nombre del archivo
        $filename = $document->document_number . '.pdf';
        
        return $pdf->download($filename);
    }
}

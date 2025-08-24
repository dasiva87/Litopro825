<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DocumentPdfController extends Controller
{
    public function show(Document $document)
    {
        // Verificar que el usuario tenga acceso al documento (multi-tenant)
        if ($document->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        // Por ahora, generamos un PDF simple con los datos del documento
        // En el futuro se puede integrar con librerías como DomPDF o Snappy
        
        $html = $this->generateDocumentHtml($document);
        
        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'inline; filename="' . $document->number . '.html"');
    }

    private function generateDocumentHtml(Document $document): string
    {
        return view('documents.pdf', compact('document'))->render();
    }
}

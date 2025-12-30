<?php

namespace App\Notifications;

use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteSent extends Notification
{
    use Queueable;

    public function __construct(
        public int $documentId
    ) {}

    protected function getDocument(): Document
    {
        return Document::with(['company', 'contact', 'clientCompany', 'documentType', 'items.itemable'])->findOrFail($this->documentId);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $document = $this->getDocument();

        // Generar PDF usando DomPDF (mismo método que DocumentPdfController)
        $pdf = Pdf::loadView('documents.pdf', compact('document'))
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'defaultPaperSize' => 'letter',
            ]);

        $documentTypeName = $document->documentType->name ?? 'Documento';

        return (new MailMessage)
            ->subject("Nueva {$documentTypeName} #{$document->document_number}")
            ->markdown('emails.quote.sent', [
                'document' => $document,
            ])
            ->attachData($pdf->output(), "cotizacion-{$document->document_number}.pdf", [
                'mime' => 'application/pdf',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $document = $this->getDocument();

        // Obtener nombre del cliente (Company o Contact)
        $clientName = $document->clientCompany->name
            ?? $document->contact->name
            ?? 'Sin cliente';

        $documentTypeName = $document->documentType->name ?? 'Documento';

        return [
            'format' => 'filament',
            'title' => "{$documentTypeName} Enviada",
            'body' => "#{$document->document_number} enviada a {$clientName}",
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'Ver Documento',
                    'url' => url("/admin/documents/{$document->id}"),
                ],
            ],
            // Campos adicionales para uso interno
            'document_id' => $document->id,
            'document_number' => $document->document_number,
            'client_name' => $clientName,
            'total' => $document->total,
        ];
    }
}

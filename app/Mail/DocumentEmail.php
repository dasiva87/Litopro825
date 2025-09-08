<?php

namespace App\Mail;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentEmail extends Mailable
{
    use Queueable, SerializesModels;

    public Document $document;
    public array $emailData;

    /**
     * Create a new message instance.
     */
    public function __construct(Document $document, array $emailData = [])
    {
        $this->document = $document;
        $this->emailData = $emailData;
        
        // Cargar relaciones necesarias
        $this->document->load([
            'company',
            'contact',
            'documentType',
            'items.itemable'
        ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->emailData['subject'] ?? 
                   "{$this->document->documentType->name} {$this->document->document_number}";
        
        return new Envelope(
            subject: $subject,
            from: new Address(
                $this->document->company->email,
                $this->document->company->name
            )
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.document',
            with: [
                'document' => $this->document,
                'emailData' => $this->emailData,
                'company' => $this->document->company,
                'contact' => $this->document->contact,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        // Generar PDF del documento
        $pdf = Pdf::loadView('documents.pdf', ['document' => $this->document])
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial'
            ]);

        $fileName = "{$this->document->documentType->name}_{$this->document->document_number}.pdf";

        return [
            Attachment::fromData(fn () => $pdf->output(), $fileName)
                ->withMime('application/pdf')
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\CollectionAccount;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CollectionAccountSent extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Número de intentos antes de fallar
     */
    public int $tries = 3;

    /**
     * Segundos de espera entre reintentos
     */
    public int $backoff = 30;

    public function __construct(
        public int $collectionAccountId
    ) {
        $this->onQueue('emails');
    }

    protected function getCollectionAccount(): CollectionAccount
    {
        return CollectionAccount::with(['clientCompany', 'company', 'documentItems'])->findOrFail($this->collectionAccountId);
    }

    public function via(object $notifiable): array
    {
        return ['mail']; // Enviar por email
    }

    public function toMail(object $notifiable): MailMessage
    {
        $collectionAccount = $this->getCollectionAccount();

        // Generar PDF usando DomPDF
        $pdf = Pdf::loadView('collection-accounts.pdf', [
            'collectionAccount' => $collectionAccount
        ])
        ->setPaper('letter', 'portrait')
        ->setOptions([
            'defaultFont' => 'Arial',
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'dpi' => 150,
            'defaultPaperSize' => 'letter',
        ]);

        // Obtener nombre del destinatario (usuario o empresa)
        $recipientName = $notifiable->name ?? $collectionAccount->clientCompany->name ?? 'Estimado cliente';
        $companyName = $collectionAccount->company->name ?? 'GrafiRed';

        return (new MailMessage)
            ->subject("{$companyName} - Nueva Cuenta de Cobro #{$collectionAccount->account_number}")
            ->replyTo($collectionAccount->company->email ?? config('mail.from.address'), $companyName)
            ->markdown('emails.collection-account.sent', [
                'collectionAccount' => $collectionAccount,
                'recipientName' => $recipientName,
            ])
            ->attachData($pdf->output(), "cuenta-cobro-{$collectionAccount->account_number}.pdf", [
                'mime' => 'application/pdf',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $collectionAccount = $this->getCollectionAccount();

        $clientName = $collectionAccount->clientCompany->name ?? 'Sin cliente';

        return [
            'format' => 'filament',
            'title' => 'Cuenta de Cobro Enviada',
            'body' => "#{$collectionAccount->account_number} enviada a {$clientName}",
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'Ver Cuenta',
                    'url' => url("/admin/collection-accounts/{$collectionAccount->id}"),
                ],
            ],
            // Campos adicionales para uso interno
            'collection_account_id' => $collectionAccount->id,
            'account_number' => $collectionAccount->account_number,
            'client_company' => $clientName,
            'total_amount' => $collectionAccount->total_amount,
        ];
    }
}

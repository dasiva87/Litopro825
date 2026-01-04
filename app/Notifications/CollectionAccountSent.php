<?php

namespace App\Notifications;

use App\Models\CollectionAccount;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CollectionAccountSent extends Notification
{
    use Queueable;

    public function __construct(
        public int $collectionAccountId
    ) {}

    protected function getCollectionAccount(): CollectionAccount
    {
        return CollectionAccount::with(['clientCompany', 'company', 'documentItems'])->findOrFail($this->collectionAccountId);
    }

    public function via(object $notifiable): array
    {
        return ['database']; // Solo notificación interna en base de datos, NO email
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

        return (new MailMessage)
            ->subject("Nueva Cuenta de Cobro #{$collectionAccount->account_number}")
            ->greeting("¡Hola {$recipientName}!")
            ->line("Se ha generado una nueva cuenta de cobro para su revisión.")
            ->line("**Número de Cuenta:** {$collectionAccount->account_number}")
            ->line("**Total:** $".number_format($collectionAccount->total_amount, 2))
            ->line("**Fecha de Emisión:** {$collectionAccount->issue_date->format('d/m/Y')}")
            ->line("**Fecha de Vencimiento:** ".($collectionAccount->due_date ? $collectionAccount->due_date->format('d/m/Y') : 'No definida'))
            ->action('Ver Cuenta de Cobro', url("/admin/collection-accounts/{$collectionAccount->id}"))
            ->line('Gracias por su preferencia.')
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

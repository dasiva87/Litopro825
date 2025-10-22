<?php

namespace App\Notifications;

use App\Models\CollectionAccount;
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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $collectionAccount = $this->getCollectionAccount();

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
            ->line('Gracias por su preferencia.');
    }

    public function toArray(object $notifiable): array
    {
        $collectionAccount = $this->getCollectionAccount();

        return [
            'collection_account_id' => $collectionAccount->id,
            'account_number' => $collectionAccount->account_number,
            'client_company' => $collectionAccount->clientCompany->name ?? 'Sin cliente',
            'total_amount' => $collectionAccount->total_amount,
            'message' => "Nueva cuenta de cobro #{$collectionAccount->account_number} enviada a {$collectionAccount->clientCompany->name}",
        ];
    }
}

<?php

namespace App\Notifications;

use App\Enums\CollectionAccountStatus;
use App\Models\CollectionAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CollectionAccountStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public int $collectionAccountId,
        public string $oldStatusValue,
        public string $newStatusValue
    ) {}

    protected function getCollectionAccount(): CollectionAccount
    {
        return CollectionAccount::with(['clientCompany', 'company'])->findOrFail($this->collectionAccountId);
    }

    protected function getOldStatus(): CollectionAccountStatus
    {
        return CollectionAccountStatus::from($this->oldStatusValue);
    }

    protected function getNewStatus(): CollectionAccountStatus
    {
        return CollectionAccountStatus::from($this->newStatusValue);
    }

    public function via(object $notifiable): array
    {
        return ['database']; // Base de datos por defecto, mail solo cuando se usa Notification::route('mail', ...)
    }

    public function toMail(object $notifiable): MailMessage
    {
        $collectionAccount = $this->getCollectionAccount();
        $oldStatus = $this->getOldStatus();
        $newStatus = $this->getNewStatus();

        // Obtener nombre del destinatario (usuario o empresa)
        $recipientName = $notifiable->name ?? $collectionAccount->company->name ?? 'Estimado cliente';

        return (new MailMessage)
            ->subject("Cambio de Estado - Cuenta de Cobro #{$collectionAccount->account_number}")
            ->greeting("¡Hola {$recipientName}!")
            ->line("La cuenta de cobro #{$collectionAccount->account_number} ha cambiado de estado.")
            ->line("**Estado Anterior:** {$oldStatus->label()}")
            ->line("**Nuevo Estado:** {$newStatus->label()}")
            ->line('**Total:** $'.number_format($collectionAccount->total_amount, 2))
            ->action('Ver Cuenta de Cobro', url("/admin/collection-accounts/{$collectionAccount->id}"))
            ->line('Gracias por su atención.');
    }

    public function toArray(object $notifiable): array
    {
        $collectionAccount = $this->getCollectionAccount();
        $oldStatus = $this->getOldStatus();
        $newStatus = $this->getNewStatus();

        return [
            'format' => 'filament',
            'title' => 'Cambio de Estado',
            'body' => "Cuenta #{$collectionAccount->account_number}: {$oldStatus->label()} → {$newStatus->label()}",
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
            'old_status' => $this->oldStatusValue,
            'new_status' => $this->newStatusValue,
            'client_company' => $collectionAccount->clientCompany->name ?? 'Sin cliente',
        ];
    }
}

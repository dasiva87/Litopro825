<?php

namespace App\Notifications;

use App\Models\CommercialRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommercialRequestReceived extends Notification
{
    use Queueable;

    public function __construct(
        public CommercialRequest $request
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $requester = $this->request->requesterCompany;
        $typeLabel = $this->request->relationship_type === 'supplier' ? 'proveedor' : 'cliente';

        return (new MailMessage)
            ->subject('Nueva Solicitud de Relación Comercial - Grafired')
            ->greeting("¡Hola {$notifiable->name}!")
            ->line("**{$requester->name}** quiere agregarte como {$typeLabel} en la red Grafired.")
            ->when($this->request->message, function ($mail) {
                return $mail->line('**Mensaje:**')
                    ->line('"'.$this->request->message.'"');
            })
            ->action('Ver Solicitud', route('filament.admin.resources.commercial-requests.index'))
            ->line('Puedes aprobar o rechazar esta solicitud desde tu panel de control.');
    }

    public function toDatabase($notifiable): array
    {
        $requester = $this->request->requesterCompany;
        $typeLabel = $this->request->relationship_type === 'supplier' ? 'proveedor' : 'cliente';

        return [
            'format' => 'filament',
            'title' => 'Nueva Solicitud Comercial',
            'body' => "{$requester->name} quiere agregarte como {$typeLabel}",
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'Ver Solicitud',
                    'url' => url("/admin/commercial-requests/{$this->request->id}"),
                ],
            ],
            // Campos adicionales para uso interno
            'request_id' => $this->request->id,
            'requester_company_id' => $this->request->requester_company_id,
            'requester_company_name' => $requester->name,
            'relationship_type' => $this->request->relationship_type,
            'message' => $this->request->message,
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}

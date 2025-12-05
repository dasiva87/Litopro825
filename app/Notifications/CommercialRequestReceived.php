<?php

namespace App\Notifications;

use App\Models\CommercialRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CommercialRequestReceived extends Notification
{
    use Queueable;

    public function __construct(
        public CommercialRequest $request
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
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
                    ->line('"' . $this->request->message . '"');
            })
            ->action('Ver Solicitud', route('filament.admin.resources.commercial-requests.index'))
            ->line('Puedes aprobar o rechazar esta solicitud desde tu panel de control.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'request_id' => $this->request->id,
            'requester_company_id' => $this->request->requester_company_id,
            'requester_company_name' => $this->request->requesterCompany->name,
            'relationship_type' => $this->request->relationship_type,
            'message' => $this->request->message,
        ];
    }
}

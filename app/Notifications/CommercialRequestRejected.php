<?php

namespace App\Notifications;

use App\Models\CommercialRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommercialRequestRejected extends Notification
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
        $target = $this->request->targetCompany;

        return (new MailMessage)
            ->subject('Solicitud Rechazada - Grafired')
            ->greeting("Hola {$notifiable->name},")
            ->line("**{$target->name}** ha rechazado tu solicitud de relación comercial.")
            ->when($this->request->response_message, function ($mail) {
                return $mail->line('**Motivo:**')
                    ->line('"'.$this->request->response_message.'"');
            })
            ->line('Puedes intentar contactarlos directamente o buscar otras empresas en Grafired.')
            ->action('Buscar Otras Empresas',
                $this->request->relationship_type === 'supplier'
                    ? route('filament.admin.resources.suppliers.index')
                    : route('filament.admin.resources.clients.index')
            );
    }

    public function toDatabase($notifiable): array
    {
        $target = $this->request->targetCompany;

        return [
            'format' => 'filament',
            'title' => 'Solicitud Rechazada',
            'body' => "{$target->name} rechazó tu solicitud comercial",
            // Campos adicionales para uso interno
            'request_id' => $this->request->id,
            'target_company_id' => $this->request->target_company_id,
            'target_company_name' => $target->name,
            'relationship_type' => $this->request->relationship_type,
            'response_message' => $this->request->response_message,
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}

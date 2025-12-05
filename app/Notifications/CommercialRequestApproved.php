<?php

namespace App\Notifications;

use App\Models\CommercialRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CommercialRequestApproved extends Notification
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
        $target = $this->request->targetCompany;
        $typeLabel = $this->request->relationship_type === 'supplier' ? 'proveedor' : 'cliente';

        return (new MailMessage)
            ->subject('¡Solicitud Aprobada! - Grafired')
            ->greeting("¡Hola {$notifiable->name}!")
            ->line("¡Buenas noticias! **{$target->name}** ha aprobado tu solicitud.")
            ->line("Ahora puedes trabajar con ellos como {$typeLabel} en la red Grafired.")
            ->when($this->request->response_message, function ($mail) {
                return $mail->line('**Mensaje de respuesta:**')
                    ->line('"' . $this->request->response_message . '"');
            })
            ->action('Ver Contacto', route('filament.admin.resources.contacts.index'))
            ->line('El contacto ya está disponible en tu lista de ' .
                   ($this->request->relationship_type === 'supplier' ? 'proveedores' : 'clientes') . '.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'request_id' => $this->request->id,
            'target_company_id' => $this->request->target_company_id,
            'target_company_name' => $this->request->targetCompany->name,
            'relationship_type' => $this->request->relationship_type,
            'response_message' => $this->request->response_message,
        ];
    }
}

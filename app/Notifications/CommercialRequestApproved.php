<?php

namespace App\Notifications;

use App\Models\CommercialRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommercialRequestApproved extends Notification
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
        $typeLabel = $this->request->relationship_type === 'supplier' ? 'proveedor' : 'cliente';

        return (new MailMessage)
            ->subject('¡Solicitud Aprobada! - Grafired')
            ->greeting("¡Hola {$notifiable->name}!")
            ->line("¡Buenas noticias! **{$target->name}** ha aprobado tu solicitud.")
            ->line("Ahora puedes trabajar con ellos como {$typeLabel} en la red Grafired.")
            ->when($this->request->response_message, function ($mail) {
                return $mail->line('**Mensaje de respuesta:**')
                    ->line('"'.$this->request->response_message.'"');
            })
            ->action('Ver Contacto', route('filament.admin.resources.contacts.index'))
            ->line('El contacto ya está disponible en tu lista de '.
                   ($this->request->relationship_type === 'supplier' ? 'proveedores' : 'clientes').'.');
    }

    public function toDatabase($notifiable): array
    {
        $target = $this->request->targetCompany;
        $typeLabel = $this->request->relationship_type === 'supplier' ? 'proveedor' : 'cliente';

        return [
            'format' => 'filament',
            'title' => '¡Solicitud Aprobada!',
            'body' => "{$target->name} aprobó tu solicitud como {$typeLabel}",
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'Ver Contactos',
                    'url' => url('/admin/contacts'),
                ],
            ],
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

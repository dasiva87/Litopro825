<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPassword extends Notification
{
    use Queueable;

    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Usar la ruta de Filament para reset password
        $resetUrl = url("/admin/password-reset/reset/{$this->token}?email=" . urlencode($notifiable->getEmailForPasswordReset()));

        $companyName = $notifiable->company->name ?? 'GrafiRed';

        return (new MailMessage)
            ->subject("{$companyName} - Restablecer Contraseña")
            ->greeting("¡Hola {$notifiable->name}!")
            ->line('Recibiste este correo porque solicitaste restablecer tu contraseña.')
            ->action('Restablecer Contraseña', $resetUrl)
            ->line('Este enlace expirará en 60 minutos.')
            ->line('Si no solicitaste restablecer tu contraseña, puedes ignorar este mensaje.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

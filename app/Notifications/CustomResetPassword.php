<?php

namespace App\Notifications;

use Filament\Facades\Filament;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class CustomResetPassword extends Notification
{
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
        // Generar URL firmada correctamente usando Filament
        $resetUrl = URL::temporarySignedRoute(
            'filament.admin.auth.password-reset.reset',
            now()->addHour(),
            [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]
        );

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

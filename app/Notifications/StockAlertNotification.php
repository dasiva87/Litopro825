<?php

namespace App\Notifications;

use App\Models\StockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class StockAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected StockAlert|Collection $alerts;

    protected string $type;

    /**
     * Create a new notification instance.
     */
    public function __construct(StockAlert|Collection $alerts, string $type = 'single')
    {
        $this->alerts = $alerts;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Agregar email para alertas críticas o si el usuario lo ha configurado
        if ($this->shouldSendEmail($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = new MailMessage;

        if ($this->type === 'single') {
            return $this->buildSingleAlertEmail($mailMessage);
        } else {
            return $this->buildBatchAlertEmail($mailMessage);
        }
    }

    /**
     * Build email for single alert
     */
    protected function buildSingleAlertEmail(MailMessage $mailMessage): MailMessage
    {
        $alert = $this->alerts;

        $mailMessage
            ->subject("Alerta de Stock: {$alert->type_label}")
            ->greeting("Alerta de Stock {$alert->severity_label}")
            ->line($alert->message)
            ->line("**Item:** {$alert->stockable->name}")
            ->line("**Stock Actual:** {$alert->current_stock}")
            ->line("**Stock Mínimo:** {$alert->min_stock}")
            ->line("**Disparada:** {$alert->triggered_at->format('d/m/Y H:i')}")
            ->action('Ver Detalles', url('/admin/products'));

        if ($alert->isCritical()) {
            $mailMessage
                ->error()
                ->line('⚠️ **Esta es una alerta crítica que requiere atención inmediata.**');
        }

        return $mailMessage->line('Gracias por usar GrafiRed.');
    }

    /**
     * Build email for batch alerts
     */
    protected function buildBatchAlertEmail(MailMessage $mailMessage): MailMessage
    {
        $alerts = $this->alerts;
        $criticalCount = $alerts->where('severity', 'critical')->count();
        $highCount = $alerts->where('severity', 'high')->count();

        $mailMessage
            ->subject("Resumen de Alertas de Stock ({$alerts->count()} alertas)")
            ->greeting('Resumen de Alertas de Stock')
            ->line("Se han generado {$alerts->count()} nuevas alertas de stock:");

        if ($criticalCount > 0) {
            $mailMessage->line("🔴 **{$criticalCount} alertas críticas**");
        }
        if ($highCount > 0) {
            $mailMessage->line("🟠 **{$highCount} alertas de alta prioridad**");
        }

        $mailMessage->line('**Items con alertas críticas:**');

        $criticalAlerts = $alerts->where('severity', 'critical')->take(5);
        foreach ($criticalAlerts as $alert) {
            $mailMessage->line("• {$alert->stockable->name}: {$alert->message}");
        }

        if ($criticalCount > 5) {
            $remaining = $criticalCount - 5;
            $mailMessage->line("... y {$remaining} alertas críticas más");
        }

        return $mailMessage
            ->action('Ver Todas las Alertas', url('/admin'))
            ->line('Revisa tu inventario y toma las acciones necesarias.')
            ->line('Gracias por usar GrafiRed.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        if ($this->type === 'single') {
            $alert = $this->alerts;

            return [
                'format' => 'filament',
                'title' => $alert->title ?: "Alerta de Stock: {$alert->type_label}",
                'body' => $alert->message,
                'actions' => [
                    [
                        'name' => 'view',
                        'label' => 'Ver Detalles',
                        'url' => url('/admin/products'),
                    ],
                ],
                // Campos adicionales para uso interno
                'alert_id' => $alert->id,
                'type' => 'stock_alert',
                'severity' => $alert->severity,
                'item_name' => $alert->stockable->name,
                'item_type' => class_basename($alert->stockable_type),
                'current_stock' => $alert->current_stock,
                'min_stock' => $alert->min_stock,
            ];
        } else {
            $alerts = $this->alerts;

            return [
                'format' => 'filament',
                'title' => 'Múltiples Alertas de Stock',
                'body' => "Se han generado {$alerts->count()} nuevas alertas de stock",
                'actions' => [
                    [
                        'name' => 'view',
                        'label' => 'Ver Alertas',
                        'url' => url('/admin/stock-management'),
                    ],
                ],
                // Campos adicionales para uso interno
                'type' => 'stock_alerts_batch',
                'total_alerts' => $alerts->count(),
                'critical_count' => $alerts->where('severity', 'critical')->count(),
                'high_count' => $alerts->where('severity', 'high')->count(),
            ];
        }
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Determine if email should be sent
     */
    protected function shouldSendEmail(object $notifiable): bool
    {
        // Enviar email siempre para alertas críticas
        if ($this->type === 'single' && $this->alerts->isCritical()) {
            return true;
        }

        if ($this->type === 'batch' && $this->alerts->where('severity', 'critical')->count() > 0) {
            return true;
        }

        // TODO: Verificar preferencias del usuario
        // return $notifiable->notification_preferences['stock_alerts_email'] ?? false;

        return false; // Por defecto, solo notificaciones en dashboard
    }

    /**
     * Get notification priority
     */
    public function getPriority(): string
    {
        if ($this->type === 'single') {
            return match ($this->alerts->severity) {
                'critical' => 'high',
                'high' => 'medium',
                default => 'low'
            };
        }

        // Para batch, basarse en la alerta más crítica
        $maxSeverity = $this->alerts->max('severity');

        return match ($maxSeverity) {
            'critical' => 'high',
            'high' => 'medium',
            default => 'low'
        };
    }

    /**
     * Customize queue delay based on priority
     */
    public function getDelay(): ?\DateTimeInterface
    {
        $priority = $this->getPriority();

        return match ($priority) {
            'high' => null, // Inmediato
            'medium' => now()->addMinutes(2),
            'low' => now()->addMinutes(5),
            default => null
        };
    }
}

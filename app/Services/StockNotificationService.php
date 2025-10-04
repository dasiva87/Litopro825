<?php

namespace App\Services;

use App\Models\StockAlert;
use App\Models\User;
use App\Notifications\StockAlertNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use App\Services\TenantContext;

class StockNotificationService
{
    /**
     * Enviar notificación para una alerta específica
     */
    public function notifyAlert(StockAlert $alert): void
    {
        $users = $this->getUsersToNotify($alert->company_id, $alert->severity);

        foreach ($users as $user) {
            $user->notify(new StockAlertNotification($alert, 'single'));
        }
    }

    /**
     * Enviar notificaciones batch para múltiples alertas
     */
    public function notifyBatchAlerts(Collection $alerts): void
    {
        if ($alerts->isEmpty()) {
            return;
        }

        $companyId = $alerts->first()->company_id;
        $users = $this->getUsersToNotify($companyId, 'batch');

        foreach ($users as $user) {
            $user->notify(new StockAlertNotification($alerts, 'batch'));
        }
    }

    /**
     * Enviar resumen diario de alertas
     */
    public function sendDailySummary(?int $companyId = null): array
    {
        $companyId = $companyId ?? TenantContext::id();
        $results = ['sent' => 0, 'skipped' => 0];

        // Obtener alertas activas del día
        $todayAlerts = StockAlert::forTenant($companyId)
            ->active()
            ->where('triggered_at', '>=', now()->startOfDay())
            ->with(['stockable'])
            ->get();

        if ($todayAlerts->isEmpty()) {
            $results['skipped'] = 1;
            return $results;
        }

        $users = $this->getUsersToNotify($companyId, 'daily_summary');

        foreach ($users as $user) {
            if ($this->shouldSendDailySummary($user, $todayAlerts)) {
                $user->notify(new StockAlertNotification($todayAlerts, 'batch'));
                $results['sent']++;
            } else {
                $results['skipped']++;
            }
        }

        return $results;
    }

    /**
     * Enviar notificaciones inmediatas para alertas críticas
     */
    public function sendCriticalAlerts(?int $companyId = null): array
    {
        $companyId = $companyId ?? TenantContext::id();

        $criticalAlerts = StockAlert::forTenant($companyId)
            ->critical()
            ->active()
            ->where('created_at', '>=', now()->subHours(1)) // Solo alertas de la última hora
            ->with(['stockable'])
            ->get();

        $results = ['sent' => 0, 'alerts' => $criticalAlerts->count()];

        if ($criticalAlerts->isEmpty()) {
            return $results;
        }

        $users = $this->getUsersToNotify($companyId, 'critical');

        foreach ($criticalAlerts as $alert) {
            foreach ($users as $user) {
                $user->notify(new StockAlertNotification($alert, 'single'));
                $results['sent']++;
            }
        }

        return $results;
    }

    /**
     * Obtener usuarios que deben recibir notificaciones
     */
    protected function getUsersToNotify(int $companyId, string $severity): Collection
    {
        $query = User::forTenant($companyId);

        // Filtrar por roles dependiendo de la severidad
        if (in_array($severity, ['critical', 'high'])) {
            // Alertas críticas: notificar a administradores y managers
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'manager', 'super-admin']);
            });
        } else {
            // Alertas normales: solo administradores
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'super-admin']);
            });
        }

        return $query->get();
    }

    /**
     * Verificar si debe enviar resumen diario al usuario
     */
    protected function shouldSendDailySummary(User $user, Collection $alerts): bool
    {
        // TODO: Verificar preferencias del usuario
        // if (!($user->notification_preferences['daily_summary'] ?? true)) {
        //     return false;
        // }

        // Enviar solo si hay alertas críticas o más de 3 alertas
        $criticalCount = $alerts->where('severity', 'critical')->count();
        $totalCount = $alerts->count();

        return $criticalCount > 0 || $totalCount >= 3;
    }

    /**
     * Enviar notificación de predicción de agotamiento
     */
    public function notifyDepletionPrediction(int $companyId, array $predictions): void
    {
        $urgentPredictions = collect($predictions)->filter(function ($prediction) {
            return $prediction['days_until_depletion'] <= 3;
        });

        if ($urgentPredictions->isEmpty()) {
            return;
        }

        $users = $this->getUsersToNotify($companyId, 'high');

        foreach ($users as $user) {
            // Crear notificación custom para predicciones
            $notification = [
                'type' => 'depletion_prediction',
                'title' => 'Predicción de Agotamiento de Stock',
                'message' => "Se prevé que {$urgentPredictions->count()} items se agoten en los próximos 3 días",
                'urgent_items' => $urgentPredictions->pluck('item_name')->take(3)->toArray(),
                'total_urgent' => $urgentPredictions->count(),
                'action_url' => url('/admin'),
                'created_at' => now(),
            ];

            $user->notify(new \Illuminate\Notifications\DatabaseNotification([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\Notifications\DepletionPredictionNotification',
                'notifiable_type' => get_class($user),
                'notifiable_id' => $user->id,
                'data' => $notification,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Marcar alertas como notificadas
     */
    public function markAlertsAsNotified(Collection $alerts): void
    {
        $alertIds = $alerts->pluck('id')->toArray();

        StockAlert::whereIn('id', $alertIds)->update([
            'metadata->notified_at' => now(),
            'metadata->notification_sent' => true,
        ]);
    }

    /**
     * Obtener estadísticas de notificaciones
     */
    public function getNotificationStats(?int $companyId = null, int $days = 7): array
    {
        $companyId = $companyId ?? TenantContext::id();
        $startDate = now()->subDays($days);

        return [
            'period_days' => $days,
            'total_alerts_created' => StockAlert::forTenant($companyId)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'alerts_notified' => StockAlert::forTenant($companyId)
                ->where('created_at', '>=', $startDate)
                ->whereJsonPath('metadata->notification_sent', true)
                ->count(),
            'critical_alerts' => StockAlert::forTenant($companyId)
                ->where('created_at', '>=', $startDate)
                ->where('severity', 'critical')
                ->count(),
            'users_with_notifications' => User::forTenant($companyId)
                ->whereHas('notifications', function ($q) use ($startDate) {
                    $q->where('created_at', '>=', $startDate)
                      ->where('data->type', 'stock_alert');
                })
                ->count(),
        ];
    }

    /**
     * Limpiar notificaciones antiguas
     */
    public function cleanupOldNotifications(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        return \Illuminate\Notifications\DatabaseNotification::where('created_at', '<', $cutoffDate)
            ->whereIn('type', [
                'App\Notifications\StockAlertNotification',
                'App\Notifications\DepletionPredictionNotification',
            ])
            ->delete();
    }

    /**
     * Enviar notificación de test
     */
    public function sendTestNotification(User $user): void
    {
        // Crear alerta de prueba
        $testAlert = new StockAlert([
            'company_id' => $user->company_id,
            'type' => 'low_stock',
            'severity' => 'medium',
            'title' => 'Prueba de Notificación',
            'message' => 'Esta es una notificación de prueba del sistema de alertas de stock.',
            'current_stock' => 5,
            'min_stock' => 10,
            'triggered_at' => now(),
        ]);

        // Crear stockable ficticio
        $testStockable = new \stdClass();
        $testStockable->name = 'Item de Prueba';

        $testAlert->setRelation('stockable', $testStockable);

        $user->notify(new StockAlertNotification($testAlert, 'single'));
    }

    /**
     * Configurar preferencias de notificación del usuario
     */
    public function updateUserNotificationPreferences(User $user, array $preferences): void
    {
        $currentPreferences = $user->notification_preferences ?? [];

        $newPreferences = array_merge($currentPreferences, [
            'stock_alerts_email' => $preferences['email'] ?? false,
            'stock_alerts_database' => $preferences['database'] ?? true,
            'critical_alerts_immediate' => $preferences['critical_immediate'] ?? true,
            'daily_summary' => $preferences['daily_summary'] ?? true,
            'depletion_predictions' => $preferences['predictions'] ?? true,
        ]);

        $user->update(['notification_preferences' => $newPreferences]);
    }
}
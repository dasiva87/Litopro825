<?php

namespace App\Services;

use App\Models\StockAlert;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockAlertService
{
    /**
     * Evaluar todas las alertas para un item específico
     */
    public function evaluateAlerts(Model $stockable): array
    {
        $alerts = [];

        // Evaluar diferentes tipos de alertas
        $alerts = array_merge($alerts, $this->evaluateStockLevelAlerts($stockable));
        $alerts = array_merge($alerts, $this->evaluateMovementAnomalies($stockable));

        return $alerts;
    }

    /**
     * Evaluar alertas de nivel de stock
     */
    protected function evaluateStockLevelAlerts(Model $stockable): array
    {
        $alerts = [];
        $currentStock = $stockable->stock;
        $minStock = $stockable->min_stock ?? 0;

        // Sin stock
        if ($currentStock <= 0) {
            $alerts[] = $this->createAlert($stockable, [
                'type' => 'out_of_stock',
                'severity' => 'critical',
                'title' => 'Stock Agotado',
                'message' => "El item '{$stockable->name}' se ha quedado sin stock",
                'current_stock' => $currentStock,
                'min_stock' => $minStock,
                'threshold_value' => 0,
            ]);
        }
        // Stock crítico (menos del 20% del mínimo)
        elseif ($minStock > 0 && $currentStock <= ($minStock * 0.2)) {
            $threshold = (int) ($minStock * 0.2);
            $alerts[] = $this->createAlert($stockable, [
                'type' => 'critical_low',
                'severity' => 'critical',
                'title' => 'Stock Crítico',
                'message' => "El item '{$stockable->name}' tiene stock crítico ({$currentStock} unidades)",
                'current_stock' => $currentStock,
                'min_stock' => $minStock,
                'threshold_value' => $threshold,
            ]);
        }
        // Stock bajo
        elseif ($minStock > 0 && $currentStock <= $minStock) {
            $alerts[] = $this->createAlert($stockable, [
                'type' => 'low_stock',
                'severity' => 'high',
                'title' => 'Stock Bajo',
                'message' => "El item '{$stockable->name}' tiene stock bajo ({$currentStock} de {$minStock} mínimo)",
                'current_stock' => $currentStock,
                'min_stock' => $minStock,
                'threshold_value' => $minStock,
            ]);
        }
        // Punto de reorden (150% del mínimo)
        elseif ($minStock > 0 && $currentStock <= ($minStock * 1.5)) {
            $threshold = (int) ($minStock * 1.5);
            $alerts[] = $this->createAlert($stockable, [
                'type' => 'reorder_point',
                'severity' => 'medium',
                'title' => 'Punto de Reorden',
                'message' => "El item '{$stockable->name}' ha alcanzado el punto de reorden ({$currentStock} unidades)",
                'current_stock' => $currentStock,
                'min_stock' => $minStock,
                'threshold_value' => $threshold,
                'expires_at' => now()->addDays(7), // Expira en 7 días
            ]);
        }

        return $alerts;
    }

    /**
     * Evaluar anomalías en movimientos
     */
    protected function evaluateMovementAnomalies(Model $stockable): array
    {
        $alerts = [];

        // Buscar movimientos inusuales en las últimas 24 horas
        $recentMovements = $stockable->stockMovements()
            ->where('created_at', '>=', now()->subDay())
            ->get();

        if ($recentMovements->isEmpty()) {
            return $alerts;
        }

        // Detectar salidas masivas (más del 50% del stock en un día)
        $totalOutbound = $recentMovements
            ->where('type', 'out')
            ->sum('quantity');

        if ($totalOutbound > ($stockable->stock * 0.5) && $totalOutbound > 10) {
            $alerts[] = $this->createAlert($stockable, [
                'type' => 'movement_anomaly',
                'severity' => 'high',
                'title' => 'Movimiento Anómalo',
                'message' => "Salida masiva detectada: {$totalOutbound} unidades en las últimas 24 horas",
                'current_stock' => $stockable->stock,
                'threshold_value' => $totalOutbound,
                'metadata' => [
                    'anomaly_type' => 'massive_outbound',
                    'period' => '24h',
                    'quantity' => $totalOutbound,
                ],
                'expires_at' => now()->addHours(48),
            ]);
        }

        // Detectar múltiples ajustes
        $adjustmentCount = $recentMovements
            ->where('type', 'adjustment')
            ->count();

        if ($adjustmentCount >= 3) {
            $alerts[] = $this->createAlert($stockable, [
                'type' => 'movement_anomaly',
                'severity' => 'medium',
                'title' => 'Múltiples Ajustes',
                'message' => "Se han realizado {$adjustmentCount} ajustes en las últimas 24 horas",
                'current_stock' => $stockable->stock,
                'threshold_value' => $adjustmentCount,
                'metadata' => [
                    'anomaly_type' => 'multiple_adjustments',
                    'period' => '24h',
                    'count' => $adjustmentCount,
                ],
                'expires_at' => now()->addHours(24),
            ]);
        }

        return $alerts;
    }

    /**
     * Crear una alerta si no existe una similar activa
     */
    protected function createAlert(Model $stockable, array $data): ?StockAlert
    {
        // Verificar si ya existe una alerta similar activa
        $existingAlert = StockAlert::where('stockable_type', get_class($stockable))
            ->where('stockable_id', $stockable->id)
            ->where('type', $data['type'])
            ->active()
            ->first();

        if ($existingAlert) {
            // Actualizar la alerta existente si es necesario
            $existingAlert->update([
                'current_stock' => $data['current_stock'],
                'threshold_value' => $data['threshold_value'] ?? $existingAlert->threshold_value,
                'message' => $data['message'],
            ]);
            return $existingAlert;
        }

        // Crear nueva alerta
        return StockAlert::create(array_merge($data, [
            'company_id' => $stockable->company_id,
            'stockable_type' => get_class($stockable),
            'stockable_id' => $stockable->id,
            'triggered_at' => now(),
            'auto_resolvable' => true,
        ]));
    }

    /**
     * Evaluar todas las alertas de la empresa
     */
    public function evaluateAllAlerts(?int $companyId = null): array
    {
        $companyId = $companyId ?? config('tenant.company_id');
        $results = ['created' => 0, 'updated' => 0, 'resolved' => 0];

        // Evaluar productos
        $products = \App\Models\Product::where('company_id', $companyId)
            ->where('active', true)
            ->get();

        foreach ($products as $product) {
            $alerts = $this->evaluateAlerts($product);
            $results['created'] += count($alerts);
        }

        // Evaluar papeles
        $papers = \App\Models\Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        foreach ($papers as $paper) {
            $alerts = $this->evaluateAlerts($paper);
            $results['created'] += count($alerts);
        }

        // Auto-resolver alertas que ya no aplican
        $resolved = $this->autoResolveAlerts($companyId);
        $results['resolved'] = $resolved;

        return $results;
    }

    /**
     * Auto-resolver alertas que ya no aplican
     */
    public function autoResolveAlerts(?int $companyId = null): int
    {
        $companyId = $companyId ?? config('tenant.company_id');
        $resolved = 0;

        $activeAlerts = StockAlert::where('company_id', $companyId)
            ->unresolved()
            ->with('stockable')
            ->get();

        foreach ($activeAlerts as $alert) {
            if ($alert->shouldAutoResolve()) {
                $alert->resolve();
                $resolved++;
            }
        }

        return $resolved;
    }

    /**
     * Obtener resumen de alertas
     */
    public function getAlertsSummary(?int $companyId = null): array
    {
        $companyId = $companyId ?? config('tenant.company_id');

        $query = StockAlert::where('company_id', $companyId);

        return [
            'total_active' => $query->clone()->active()->count(),
            'total_unresolved' => $query->clone()->unresolved()->count(),
            'critical' => $query->clone()->where('severity', 'critical')->unresolved()->count(),
            'high' => $query->clone()->where('severity', 'high')->unresolved()->count(),
            'medium' => $query->clone()->where('severity', 'medium')->unresolved()->count(),
            'low' => $query->clone()->where('severity', 'low')->unresolved()->count(),
            'by_type' => $query->clone()->unresolved()
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'oldest_unresolved' => $query->clone()->unresolved()
                ->orderBy('triggered_at', 'asc')
                ->first()?->age_days ?? 0,
        ];
    }

    /**
     * Reconocer múltiples alertas
     */
    public function acknowledgeAlerts(array $alertIds, ?int $userId = null): int
    {
        $acknowledged = 0;

        foreach ($alertIds as $alertId) {
            $alert = StockAlert::find($alertId);
            if ($alert && $alert->acknowledge($userId)) {
                $acknowledged++;
            }
        }

        return $acknowledged;
    }

    /**
     * Resolver múltiples alertas
     */
    public function resolveAlerts(array $alertIds, ?int $userId = null): int
    {
        $resolved = 0;

        foreach ($alertIds as $alertId) {
            $alert = StockAlert::find($alertId);
            if ($alert && $alert->resolve($userId)) {
                $resolved++;
            }
        }

        return $resolved;
    }

    /**
     * Limpiar alertas expiradas
     */
    public function cleanupExpiredAlerts(?int $companyId = null): int
    {
        $companyId = $companyId ?? config('tenant.company_id');

        return StockAlert::where('company_id', $companyId)
            ->expired()
            ->whereIn('status', ['active', 'acknowledged'])
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => null, // Auto-resolved
            ]);
    }
}
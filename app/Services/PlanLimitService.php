<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Plan;
use Carbon\Carbon;

class PlanLimitService
{
    protected Company $company;
    protected ?Plan $plan;

    public function __construct(Company $company)
    {
        $this->company = $company;
        $this->plan = $this->getCurrentPlan();
    }

    /**
     * Obtener el plan actual de la empresa
     */
    protected function getCurrentPlan(): ?Plan
    {
        $subscription = $this->company->subscription();

        if (!$subscription || !$subscription->active()) {
            return null;
        }

        return Plan::where('stripe_price_id', $subscription->stripe_price)->first();
    }

    /**
     * Verificar si la empresa puede crear un nuevo usuario
     */
    public function canCreateUser(): bool
    {
        if (!$this->plan) {
            return false; // Sin plan activo
        }

        $maxUsers = $this->plan->limits['max_users'] ?? 0;

        if ($maxUsers === -1) {
            return true; // Ilimitado
        }

        $currentUsers = $this->company->users()->count();

        return $currentUsers < $maxUsers;
    }

    /**
     * Verificar si la empresa puede crear un nuevo documento
     */
    public function canCreateDocument(): bool
    {
        if (!$this->plan) {
            return false; // Sin plan activo
        }

        $maxDocumentsPerMonth = $this->plan->limits['max_documents_per_month'] ?? 0;

        if ($maxDocumentsPerMonth === -1) {
            return true; // Ilimitado
        }

        // Contar documentos del mes actual
        $currentMonth = Carbon::now()->startOfMonth();
        $documentsThisMonth = $this->company->documents()
            ->where('created_at', '>=', $currentMonth)
            ->count();

        return $documentsThisMonth < $maxDocumentsPerMonth;
    }

    /**
     * Verificar si la empresa puede crear un nuevo producto
     */
    public function canCreateProduct(): bool
    {
        if (!$this->plan) {
            return false; // Sin plan activo
        }

        $maxProducts = $this->plan->limits['max_products'] ?? 0;

        if ($maxProducts === -1) {
            return true; // Ilimitado
        }

        $currentProducts = $this->company->products()->count();

        return $currentProducts < $maxProducts;
    }

    /**
     * Verificar si tiene acceso a características específicas
     */
    public function hasFeature(string $feature): bool
    {
        if (!$this->plan) {
            return false; // Sin plan activo
        }

        $limits = $this->plan->limits ?? [];

        return match ($feature) {
            'advanced_reports' => $limits['advanced_reports'] ?? false,
            'automation_features' => $limits['automation_features'] ?? false,
            'api_access' => $limits['api_access'] ?? false,
            'multi_company' => $limits['multi_company'] ?? false,
            'social_feed_access' => $limits['social_feed_access'] ?? true,
            'custom_integrations' => $limits['custom_integrations'] ?? false,
            default => false,
        };
    }

    /**
     * Obtener el uso actual de almacenamiento
     */
    public function getStorageUsage(): array
    {
        $maxStorageMb = $this->plan?->limits['max_storage_mb'] ?? 0;

        // TODO: Implementar cálculo real de almacenamiento
        $usedStorageMb = 0; // Placeholder

        return [
            'max' => $maxStorageMb,
            'used' => $usedStorageMb,
            'percentage' => $maxStorageMb > 0 ? ($usedStorageMb / $maxStorageMb) * 100 : 0,
            'unlimited' => $maxStorageMb === -1,
        ];
    }

    /**
     * Obtener información de límites de la empresa
     */
    public function getLimitsInfo(): array
    {
        if (!$this->plan) {
            return [
                'has_active_plan' => false,
                'plan_name' => null,
                'limits' => [],
            ];
        }

        $limits = $this->plan->limits ?? [];

        // Calcular uso actual
        $currentUsers = $this->company->users()->count();
        $currentProducts = $this->company->products()->count();

        $currentMonth = Carbon::now()->startOfMonth();
        $documentsThisMonth = $this->company->documents()
            ->where('created_at', '>=', $currentMonth)
            ->count();

        return [
            'has_active_plan' => true,
            'plan_name' => $this->plan->name,
            'limits' => [
                'users' => [
                    'max' => $limits['max_users'] ?? 0,
                    'current' => $currentUsers,
                    'unlimited' => ($limits['max_users'] ?? 0) === -1,
                ],
                'documents_per_month' => [
                    'max' => $limits['max_documents_per_month'] ?? 0,
                    'current' => $documentsThisMonth,
                    'unlimited' => ($limits['max_documents_per_month'] ?? 0) === -1,
                ],
                'products' => [
                    'max' => $limits['max_products'] ?? 0,
                    'current' => $currentProducts,
                    'unlimited' => ($limits['max_products'] ?? 0) === -1,
                ],
                'storage' => $this->getStorageUsage(),
                'features' => [
                    'advanced_reports' => $this->hasFeature('advanced_reports'),
                    'automation_features' => $this->hasFeature('automation_features'),
                    'api_access' => $this->hasFeature('api_access'),
                    'multi_company' => $this->hasFeature('multi_company'),
                    'social_feed_access' => $this->hasFeature('social_feed_access'),
                    'custom_integrations' => $this->hasFeature('custom_integrations'),
                ],
            ],
        ];
    }

    /**
     * Crear instancia del servicio para una empresa
     */
    public static function for(Company $company): self
    {
        return new self($company);
    }
}

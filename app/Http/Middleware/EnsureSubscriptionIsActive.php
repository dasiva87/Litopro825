<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class EnsureSubscriptionIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        $user = Auth::user();

        if (!$user || !$user->company) {
            return redirect()->route('filament.admin.auth.login');
        }

        $company = $user->company;

        // Verificar si la empresa tiene una suscripción activa
        if (!$company->hasActiveSubscription()) {
            // Permitir acceso a páginas de facturación y configuración
            if ($this->isAllowedWithoutSubscription($request)) {
                return $next($request);
            }

            // Redirigir a página de suscripción
            Notification::make()
                ->title('Suscripción Requerida')
                ->body('Tu empresa necesita una suscripción activa para acceder a esta funcionalidad.')
                ->warning()
                ->persistent()
                ->send();

            return redirect()->route('filament.admin.pages.billing');
        }

        // Si se especifica una característica, verificar límites del plan
        if ($feature) {
            if (!$this->hasFeatureAccess($company, $feature)) {
                Notification::make()
                    ->title('Función No Disponible')
                    ->body('Esta funcionalidad no está disponible en tu plan actual. Actualiza tu suscripción para acceder.')
                    ->warning()
                    ->persistent()
                    ->send();

                return redirect()->route('filament.admin.pages.billing');
            }
        }

        return $next($request);
    }

    /**
     * Verificar si la ruta es permitida sin suscripción
     */
    private function isAllowedWithoutSubscription(Request $request): bool
    {
        $allowedRoutes = [
            'filament.admin.pages.billing',
            'filament.admin.pages.company-settings',
            'filament.admin.auth.logout',
        ];

        $allowedPaths = [
            '/admin/billing',
            '/admin/company-settings',
            '/admin/logout',
        ];

        $currentRoute = $request->route()?->getName();
        $currentPath = $request->getPathInfo();

        return in_array($currentRoute, $allowedRoutes) ||
               collect($allowedPaths)->contains(fn($path) => str_starts_with($currentPath, $path));
    }

    /**
     * Verificar si el plan actual tiene acceso a una característica específica
     */
    private function hasFeatureAccess($company, string $feature): bool
    {
        // Usar el plan actual de la empresa directamente
        $plan = $company->getCurrentPlan();

        if (!$plan) {
            return false;
        }

        $limits = $plan->limits ?? [];

        return match ($feature) {
            'advanced_reports' => $limits['advanced_reports'] ?? false,
            'automation_features' => $limits['automation_features'] ?? false,
            'api_access' => $limits['api_access'] ?? false,
            'multi_company' => $limits['multi_company'] ?? false,
            'social_feed_access' => $limits['social_feed_access'] ?? true,
            default => true,
        };
    }
}

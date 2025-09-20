<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for public routes, webhooks, and guest routes
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Skip check for super admin users
        if ($user->hasRole('Super Admin')) {
            return $next($request);
        }

        // Check if user has company
        if (! $user->company) {
            Auth::logout();

            return redirect()->route('register')->with('error',
                'Tu usuario no tiene una empresa asociada. Por favor contacta soporte.'
            );
        }

        $company = $user->company;

        // Check if company is active
        if (! $company->is_active || $company->status !== 'active') {
            // Allow access to billing and payment-related pages
            $allowedRoutes = [
                'filament.admin.pages.billing',
                'registration.payment-response',
                'registration.success',
                'registration.pending',
                'registration.failed',
                'payu.webhook',
            ];

            $currentRoute = $request->route()?->getName();

            if (in_array($currentRoute, $allowedRoutes)) {
                return $next($request);
            }

            // Redirect to billing page with appropriate message based on company status
            $message = match ($company->status) {
                'pending' => 'Tu cuenta está pendiente de activación. Complete el pago para activar tu suscripción.',
                'suspended' => 'Tu cuenta ha sido suspendida. Contacta soporte para más información.',
                'cancelled' => 'Tu suscripción ha sido cancelada. Renueva tu plan para continuar.',
                default => 'Tu empresa no está activa. Verifica el estado de tu suscripción.',
            };

            return redirect()->route('filament.admin.pages.billing')->with('warning', $message);
        }

        // Check if subscription is expired
        if ($company->subscription_expires_at && $company->subscription_expires_at->isPast()) {
            return redirect()->route('filament.admin.pages.billing')->with('warning',
                'Tu suscripción ha expirado. Renueva tu plan para continuar usando LitoPro.'
            );
        }

        return $next($request);
    }
}

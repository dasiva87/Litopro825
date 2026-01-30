<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->setTenantContext($request);

        return $next($request);
    }

    /**
     * Establish tenant context safely without recursion
     */
    private function setTenantContext(Request $request): void
    {
        try {
            // Prioridad 1: Usuario autenticado - SIEMPRE usar su company_id actual
            if (auth()->check()) {
                $user = auth()->user();

                if ($user->company_id) {
                    // Verificar si cambió de empresa (sesión tiene otro valor)
                    $sessionTenantId = Session::get('current_tenant_id');

                    if ($sessionTenantId !== $user->company_id) {
                        // Actualizar sesión con el company_id correcto
                        Session::put('current_tenant_id', $user->company_id);
                    }

                    Config::set('app.current_tenant_id', $user->company_id);
                    return;
                }
            }

            // Prioridad 2: Desde sesión si existe y no hay usuario autenticado
            if (Session::has('current_tenant_id')) {
                $tenantId = Session::get('current_tenant_id');
                Config::set('app.current_tenant_id', $tenantId);
                return;
            }

            // Prioridad 3: Query directa a BD como fallback
            $userId = $request->session()->get('login_web_' . sha1('web'));

            if ($userId) {
                $companyId = DB::table('users')
                    ->where('id', $userId)
                    ->where('is_active', true)
                    ->value('company_id');

                if ($companyId) {
                    Config::set('app.current_tenant_id', $companyId);
                    Session::put('current_tenant_id', $companyId);
                }
            }

        } catch (\Exception $e) {
            // En caso de error, no establecer tenant (modo sin restricciones)
            // Log del error si es necesario
            \Log::warning('SetTenantContext error: ' . $e->getMessage());
        }
    }
}

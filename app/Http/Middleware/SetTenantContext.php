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
            // Método 1: Si ya está en config, usar eso
            if (Config::has('app.current_tenant_id')) {
                return;
            }

            // Método 2: Desde sesión si existe
            if (Session::has('current_tenant_id')) {
                $tenantId = Session::get('current_tenant_id');
                Config::set('app.current_tenant_id', $tenantId);
                return;
            }

            // Método 3A: Usar Auth::user() si está disponible (para Livewire)
            if (auth()->check()) {
                $user = auth()->user();

                if ($user->company_id) {
                    Config::set('app.current_tenant_id', $user->company_id);
                    Session::put('current_tenant_id', $user->company_id);
                    return;
                }
            }

            // Método 3B: Si hay usuario autenticado, obtener company_id directamente de la BD
            $userId = $request->session()->get('login_web_' . sha1('web'));

            if ($userId) {
                // Query directa a la BD sin usar modelos para evitar scopes
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
            // Log del error si es necesario, pero no fallar
        }
    }
}

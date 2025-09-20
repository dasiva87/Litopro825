<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Excluir rutas de autenticación del middleware
        if ($request->routeIs('filament.super-admin.auth.*')) {
            return $next($request);
        }

        // Verificar que el usuario esté autenticado
        if (! auth()->check()) {
            return redirect()->route('filament.super-admin.auth.login');
        }

        // Verificar que el usuario tenga el rol Super Admin
        if (! auth()->user()->hasRole('Super Admin')) {
            abort(403, 'No tienes permisos para acceder al panel de Super Administración.');
        }

        return $next($request);
    }
}

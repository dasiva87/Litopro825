<?php

namespace App\Http\Middleware;

use App\Enums\CompanyType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyTypeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar en rutas del admin panel
        if (!$request->is('admin/*')) {
            return $next($request);
        }

        // Verificar si el usuario está autenticado
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        $company = $user->company;

        // Si no tiene empresa, continuar
        if (!$company) {
            return $next($request);
        }

        // Obtener el resource actual de la URL
        $path = $request->path();
        $segments = explode('/', $path);

        // admin/resource-name
        if (count($segments) >= 2 && $segments[0] === 'admin') {
            $resourceName = $segments[1];

            // Recursos restringidos para papelerías
            $restrictedForPapeleria = [
                'simple-items',
                'digital-items',
                'talonario-items',
                'magazine-items',
                'printing-machines',
                'finishings'
            ];

            // Si es papelería y trata de acceder a recursos no permitidos
            if ($company->isPapeleria() && in_array($resourceName, $restrictedForPapeleria)) {
                abort(403, 'Este módulo no está disponible para empresas de tipo Papelería.');
            }
        }

        return $next($request);
    }
}

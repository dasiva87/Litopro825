<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustedProxyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force HTTPS scheme when in production and detect Railway proxy
        if (app()->environment('production')) {
            // Railway/Proxy headers detection
            $forwardedProto = $request->header('x-forwarded-proto');
            $forwardedHost = $request->header('x-forwarded-host');

            if ($forwardedProto === 'https' || $forwardedHost) {
                $request->server->set('HTTPS', 'on');
                $request->server->set('SERVER_PORT', 443);
                $request->server->set('REQUEST_SCHEME', 'https');

                // Override URL generation to use HTTPS
                url()->forceScheme('https');
            }
        }

        return $next($request);
    }
}

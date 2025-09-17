<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class TrustedProxyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Force HTTPS URLs in production when behind Railway proxy
        if (app()->environment('production')) {
            URL::forceScheme('https');

            // Trust Railway proxy headers
            $this->app['request']->server->set('HTTPS', 'on');
            $this->app['request']->server->set('SERVER_PORT', 443);
        }
    }
}

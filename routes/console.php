<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar envío de resumen diario de órdenes de pedido
Schedule::command('purchase-orders:send-digest')
    ->dailyAt('08:00')
    ->description('Enviar resumen diario de órdenes de pedido');

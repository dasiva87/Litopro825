<?php

namespace App\Listeners;

use App\Events\PurchaseOrderStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyPurchaseOrderStatusChange
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PurchaseOrderStatusChanged $event): void
    {
        //
    }
}

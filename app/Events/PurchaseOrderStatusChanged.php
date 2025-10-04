<?php

namespace App\Events;

use App\Enums\OrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PurchaseOrder $purchaseOrder,
        public OrderStatus $oldStatus,
        public OrderStatus $newStatus
    ) {
        //
    }
}

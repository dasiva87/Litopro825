<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public int $purchaseOrderId,
        public string $oldStatusValue,
        public string $newStatusValue
    ) {}

    protected function getPurchaseOrder(): PurchaseOrder
    {
        return PurchaseOrder::with(['supplierCompany', 'company'])->findOrFail($this->purchaseOrderId);
    }

    protected function getOldStatus(): OrderStatus
    {
        return OrderStatus::from($this->oldStatusValue);
    }

    protected function getNewStatus(): OrderStatus
    {
        return OrderStatus::from($this->newStatusValue);
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $purchaseOrder = $this->getPurchaseOrder();
        $oldStatus = $this->getOldStatus();
        $newStatus = $this->getNewStatus();

        return (new MailMessage)
            ->subject("Cambio de Estado - Orden #{$purchaseOrder->order_number}")
            ->markdown('emails.purchase-order.status-changed', [
                'purchaseOrder' => $purchaseOrder,
                'oldStatus' => $this->oldStatusValue,
                'newStatus' => $this->newStatusValue,
                'oldStatusLabel' => $oldStatus->getLabel(),
                'newStatusLabel' => $newStatus->getLabel(),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $purchaseOrder = $this->getPurchaseOrder();

        return [
            'purchase_order_id' => $purchaseOrder->id,
            'order_number' => $purchaseOrder->order_number,
            'old_status' => $this->oldStatusValue,
            'new_status' => $this->newStatusValue,
            'supplier_company' => $purchaseOrder->supplierCompany->name ?? 'Sin proveedor',
            'message' => "Orden #{$purchaseOrder->order_number} cambió de estado",
        ];
    }
}

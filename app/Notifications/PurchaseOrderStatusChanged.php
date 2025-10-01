<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PurchaseOrder $purchaseOrder,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = [
            'draft' => 'Borrador',
            'sent' => 'Enviada',
            'confirmed' => 'Confirmada',
            'partially_received' => 'Parcialmente Recibida',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
        ];

        $oldStatusLabel = $statusLabels[$this->oldStatus] ?? $this->oldStatus;
        $newStatusLabel = $statusLabels[$this->newStatus] ?? $this->newStatus;

        return (new MailMessage)
                    ->subject("Cambio de Estado - Orden #{$this->purchaseOrder->order_number}")
                    ->markdown('emails.purchase-order.status-changed', [
                        'purchaseOrder' => $this->purchaseOrder,
                        'oldStatus' => $this->oldStatus,
                        'newStatus' => $this->newStatus,
                        'oldStatusLabel' => $oldStatusLabel,
                        'newStatusLabel' => $newStatusLabel,
                    ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'purchase_order_id' => $this->purchaseOrder->id,
            'order_number' => $this->purchaseOrder->order_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'supplier_company' => $this->purchaseOrder->supplierCompany->name,
            'message' => "Orden #{$this->purchaseOrder->order_number} cambió de estado"
        ];
    }
}
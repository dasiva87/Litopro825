<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PurchaseOrder $purchaseOrder
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pdfService = new PurchaseOrderPdfService();
        $pdf = $pdfService->generatePdf($this->purchaseOrder);

        return (new MailMessage)
                    ->subject("Nueva Orden de Pedido #{$this->purchaseOrder->order_number}")
                    ->markdown('emails.purchase-order.created', [
                        'purchaseOrder' => $this->purchaseOrder
                    ])
                    ->attachData($pdf->output(), "orden-pedido-{$this->purchaseOrder->order_number}.pdf", [
                        'mime' => 'application/pdf',
                    ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'purchase_order_id' => $this->purchaseOrder->id,
            'order_number' => $this->purchaseOrder->order_number,
            'supplier_company' => $this->purchaseOrder->supplierCompany->name,
            'total_amount' => $this->purchaseOrder->total_amount,
            'message' => "Nueva orden de pedido #{$this->purchaseOrder->order_number} enviada a {$this->purchaseOrder->supplierCompany->name}"
        ];
    }
}
<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderCreated extends Notification
{
    use Queueable;

    public function __construct(
        public int $purchaseOrderId
    ) {}

    protected function getPurchaseOrder(): PurchaseOrder
    {
        return PurchaseOrder::with(['supplierCompany', 'company', 'documentItems'])->findOrFail($this->purchaseOrderId);
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $purchaseOrder = $this->getPurchaseOrder();
        $pdfService = new PurchaseOrderPdfService;
        $pdf = $pdfService->generatePdf($purchaseOrder);

        return (new MailMessage)
            ->subject("Nueva Orden de Pedido #{$purchaseOrder->order_number}")
            ->markdown('emails.purchase-order.created', [
                'purchaseOrder' => $purchaseOrder,
            ])
            ->attachData($pdf->output(), "orden-pedido-{$purchaseOrder->order_number}.pdf", [
                'mime' => 'application/pdf',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $purchaseOrder = $this->getPurchaseOrder();

        return [
            'purchase_order_id' => $purchaseOrder->id,
            'order_number' => $purchaseOrder->order_number,
            'supplier_company' => $purchaseOrder->supplierCompany->name ?? 'Sin proveedor',
            'total_amount' => $purchaseOrder->total_amount,
            'message' => "Nueva orden de pedido #{$purchaseOrder->order_number} enviada a {$purchaseOrder->supplierCompany->name}",
        ];
    }
}

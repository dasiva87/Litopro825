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
        return PurchaseOrder::with(['supplierCompany', 'supplier', 'company', 'documentItems'])->findOrFail($this->purchaseOrderId);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
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

        // Obtener nombre del proveedor (Company o Contact)
        $supplierName = $purchaseOrder->supplierCompany->name
            ?? $purchaseOrder->supplier->name
            ?? 'Sin proveedor';

        return [
            'format' => 'filament',
            'title' => 'Orden de Pedido Creada',
            'body' => "Nueva orden #{$purchaseOrder->order_number} enviada a {$supplierName}",
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'Ver Orden',
                    'url' => url("/admin/purchase-orders/{$purchaseOrder->id}"),
                ],
            ],
            // Campos adicionales para uso interno
            'purchase_order_id' => $purchaseOrder->id,
            'order_number' => $purchaseOrder->order_number,
            'supplier_company' => $supplierName,
            'total_amount' => $purchaseOrder->total_amount,
        ];
    }
}

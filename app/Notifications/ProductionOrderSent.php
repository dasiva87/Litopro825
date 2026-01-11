<?php

namespace App\Notifications;

use App\Models\ProductionOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductionOrderSent extends Notification
{
    use Queueable;

    public function __construct(
        public int $productionOrderId
    ) {}

    protected function getProductionOrder(): ProductionOrder
    {
        return ProductionOrder::with([
            'company',
            'supplier',
            'supplierCompany',
            'operator',
            'documentItems.itemable',
            'documentItems.document',
        ])->findOrFail($this->productionOrderId);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $productionOrder = $this->getProductionOrder();

        // Generar PDF usando DomPDF
        $pdf = Pdf::loadView('production-orders.pdf', compact('productionOrder'))
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'defaultPaperSize' => 'letter',
            ]);

        // Obtener nombre del operador o proveedor
        $recipientName = $productionOrder->operator->name
            ?? $productionOrder->supplierCompany->name
            ?? $productionOrder->supplier->name
            ?? 'Estimado operador';

        $companyName = $productionOrder->company->name ?? 'GrafiRed';

        return (new MailMessage)
            ->subject("{$companyName} - Nueva Orden de Producción #{$productionOrder->production_number}")
            ->markdown('emails.production-order.sent', [
                'productionOrder' => $productionOrder,
            ])
            ->attachData($pdf->output(), "orden-produccion-{$productionOrder->production_number}.pdf", [
                'mime' => 'application/pdf',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $productionOrder = $this->getProductionOrder();

        // Obtener nombre del operador o proveedor
        $operatorName = $productionOrder->operator->name
            ?? $productionOrder->supplierCompany->name
            ?? $productionOrder->supplier->name
            ?? 'Sin asignar';

        return [
            'format' => 'filament',
            'title' => 'Orden de Producción Enviada',
            'body' => "#{$productionOrder->production_number} enviada a {$operatorName}",
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'Ver Orden',
                    'url' => url("/admin/production-orders/{$productionOrder->id}"),
                ],
            ],
            // Campos adicionales para uso interno
            'production_order_id' => $productionOrder->id,
            'production_number' => $productionOrder->production_number,
            'operator_name' => $operatorName,
            'total_items' => $productionOrder->total_items,
        ];
    }
}

<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class PurchaseOrderPdfService
{
    public function generatePdf(PurchaseOrder $order): \Barryvdh\DomPDF\PDF
    {
        $order->load([
            'supplierCompany',
            'company',
            'purchaseOrderItems.documentItem',
            'purchaseOrderItems.paper',
            'createdBy',
            'approvedBy'
        ]);

        // Obtener documentos únicos relacionados
        $documents = $order->documents();

        $data = [
            'order' => $order,
            'company' => $order->company,
            'supplier' => $order->supplierCompany,
            'documents' => $documents, // Múltiples cotizaciones
        ];

        $pdf = Pdf::loadView('pdf.purchase-order', $data);

        $pdf->setPaper('letter', 'portrait');

        return $pdf;
    }

    public function downloadPdf(PurchaseOrder $order): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = $this->generatePdf($order);

        $filename = "orden-pedido-{$order->order_number}.pdf";

        return $pdf->download($filename);
    }

    public function streamPdf(PurchaseOrder $order): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = $this->generatePdf($order);

        $filename = "orden-pedido-{$order->order_number}.pdf";

        return $pdf->stream($filename);
    }

    public function savePdf(PurchaseOrder $order, string $path = null): string
    {
        $pdf = $this->generatePdf($order);

        if (!$path) {
            $path = storage_path("app/purchase-orders/orden-pedido-{$order->order_number}.pdf");
        }

        // Crear directorio si no existe
        $directory = dirname($path);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $pdf->save($path);

        return $path;
    }

    public function emailPdf(PurchaseOrder $order, array $recipients): bool
    {
        try {
            $pdf = $this->generatePdf($order);
            $filename = "orden-pedido-{$order->order_number}.pdf";

            \Mail::send('emails.purchase-order', [
                'order' => $order,
                'supplier' => $order->supplierCompany,
                'company' => $order->company,
            ], function ($message) use ($order, $recipients, $pdf, $filename) {
                $message->to($recipients)
                        ->subject("Orden de Pedido #{$order->order_number} - {$order->company->name}")
                        ->attachData($pdf->output(), $filename, [
                            'mime' => 'application/pdf',
                        ]);
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Error sending purchase order PDF email', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
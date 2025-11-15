<?php

namespace App\Services;

use App\Models\ProductionOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class ProductionOrderPdfService
{
    public function generatePdf(ProductionOrder $order): \Barryvdh\DomPDF\PDF
    {
        $order->load([
            'supplier',
            'operator',
            'company',
            'documentItems.itemable',
            'documentItems.document',
            'qualityCheckedBy'
        ]);

        $data = [
            'order' => $order,
            'company' => $order->company,
            'supplier' => $order->supplier,
            'operator' => $order->operator,
        ];

        $pdf = Pdf::loadView('pdf.production-order', $data);

        $pdf->setPaper('letter', 'portrait');

        return $pdf;
    }

    public function downloadPdf(ProductionOrder $order): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = $this->generatePdf($order);

        $filename = "orden-produccion-{$order->production_number}.pdf";

        return $pdf->download($filename);
    }

    public function streamPdf(ProductionOrder $order): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = $this->generatePdf($order);

        $filename = "orden-produccion-{$order->production_number}.pdf";

        return $pdf->stream($filename);
    }

    public function savePdf(ProductionOrder $order, string $path = null): string
    {
        $pdf = $this->generatePdf($order);

        if (!$path) {
            $path = storage_path("app/production-orders/orden-produccion-{$order->production_number}.pdf");
        }

        // Crear directorio si no existe
        $directory = dirname($path);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $pdf->save($path);

        return $path;
    }

    public function emailPdf(ProductionOrder $order, array $recipients): bool
    {
        try {
            $pdf = $this->generatePdf($order);
            $filename = "orden-produccion-{$order->production_number}.pdf";

            \Mail::send('emails.production-order', [
                'order' => $order,
                'supplier' => $order->supplier,
                'operator' => $order->operator,
                'company' => $order->company,
            ], function ($message) use ($order, $recipients, $pdf, $filename) {
                $message->to($recipients)
                        ->subject("Orden de Producción #{$order->production_number} - {$order->company->name}")
                        ->attachData($pdf->output(), $filename, [
                            'mime' => 'application/pdf',
                        ]);
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Error sending production order PDF email', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

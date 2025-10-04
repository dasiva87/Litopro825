<?php

namespace App\Jobs;

use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeneratePurchaseOrderPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $purchaseOrderId,
        public ?string $savePath = null,
        public bool $shouldEmail = false,
        public array $emailRecipients = []
    ) {
        $this->onQueue('pdfs');
    }

    /**
     * Execute the job.
     */
    public function handle(PurchaseOrderPdfService $pdfService): void
    {
        try {
            $order = PurchaseOrder::with([
                'supplierCompany',
                'company',
                'documentItems.itemable', // Can't eager load .paper - not all itemables have it
                'documentItems.document',
                'createdBy',
                'approvedBy'
            ])->findOrFail($this->purchaseOrderId);

            // Generar y guardar PDF
            $path = $this->savePath ?? $this->getDefaultPath($order);
            $savedPath = $pdfService->savePdf($order, $path);

            Log::info('Purchase Order PDF generated successfully', [
                'order_id' => $this->purchaseOrderId,
                'order_number' => $order->order_number,
                'path' => $savedPath
            ]);

            // Enviar email si se solicitó
            if ($this->shouldEmail && !empty($this->emailRecipients)) {
                $pdfService->emailPdf($order, $this->emailRecipients);

                Log::info('Purchase Order PDF emailed successfully', [
                    'order_id' => $this->purchaseOrderId,
                    'recipients' => $this->emailRecipients
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to generate Purchase Order PDF', [
                'order_id' => $this->purchaseOrderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Get the default storage path for the PDF
     */
    private function getDefaultPath(PurchaseOrder $order): string
    {
        $directory = storage_path('app/purchase-orders');

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        return "{$directory}/orden-pedido-{$order->order_number}.pdf";
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Purchase Order PDF generation job failed permanently', [
            'order_id' => $this->purchaseOrderId,
            'error' => $exception->getMessage()
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function __construct(private PurchaseOrderPdfService $pdfService) {}

    public function downloadPdf(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);
        return $this->pdfService->downloadPdf($purchaseOrder);
    }

    public function viewPdf(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);
        return $this->pdfService->streamPdf($purchaseOrder);
    }

    public function emailPdf(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $request->validate([
            'emails' => 'required|array',
            'emails.*' => 'required|email',
        ]);

        $sent = $this->pdfService->emailPdf($purchaseOrder, $request->emails);

        if ($sent) {
            return response()->json([
                'success' => true,
                'message' => 'Email enviado exitosamente',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error al enviar el email',
        ], 500);
    }

    /**
     * FLUJO 1: Buscar cotizaciones aprobadas para importar items
     */
    public function searchDocuments(Request $request)
    {
        $search = $request->get('search', '');
        $companyId = auth()->user()->company_id ?? config('app.current_tenant_id');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'No company context found',
            ], 400);
        }

        $query = Document::with('contact')
            ->where('company_id', $companyId)
            ->where('status', 'approved');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                  ->orWhereHas('contact', function ($contactQuery) use ($search) {
                      $contactQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('tax_id', 'like', "%{$search}%")
                                  ->orWhere('document_number', 'like', "%{$search}%");
                  });
            });
        }

        $documents = $query->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($document) {
                return [
                    'id' => $document->id,
                    'number' => $document->document_number,
                    'client' => $document->contact->name ?? 'Sin contacto',
                    'client_avatar' => strtoupper(substr($document->contact->name ?? 'SC', 0, 1)),
                    'date' => $document->created_at->format('d M Y'),
                    'formatted_date' => $document->created_at->format('d \d\e M \d\e Y'),
                    'items_count' => $document->items()->availableForOrders()->count(),
                ];
            });

        return response()->json([
            'success' => true,
            'documents' => $documents,
        ]);
    }

    /**
     * FLUJO 1: Obtener items de una cotización específica
     */
    public function getDocumentItems(Request $request, $documentId)
    {
        $companyId = auth()->user()->company_id ?? config('app.current_tenant_id');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'No company context found',
            ], 400);
        }

        $document = Document::with(['contact', 'items.itemable', 'items.purchaseOrders'])
            ->where('company_id', $companyId)
            ->where('id', $documentId)
            ->where('status', 'approved')
            ->first();

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada o no aprobada',
            ], 404);
        }

        $items = $document->items()
            ->availableForOrders()
            ->get()
            ->map(function ($item, $index) {
                $itemableInfo = $this->getItemableInfo($item);

                return [
                    'id' => $item->id,
                    'number' => str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                    'quantity' => number_format($item->quantity ?? 0),
                    'description' => $item->description ?? 'Sin descripción',
                    'unit_price' => '$' . number_format($item->unit_price ?? 0),
                    'total_price' => '$' . number_format($item->total_price ?? 0),
                    'unit_price_raw' => $item->unit_price ?? 0,
                    'total_price_raw' => $item->total_price ?? 0,
                    'quantity_raw' => $item->quantity ?? 0,
                    'itemable_type' => $item->itemable_type,
                    'itemable_id' => $item->itemable_id,
                    'in_orders_count' => $item->purchaseOrders()->count(),
                    'itemable_info' => $itemableInfo,
                ];
            });

        return response()->json([
            'success' => true,
            'document' => [
                'id' => $document->id,
                'number' => $document->document_number,
                'client_name' => $document->contact->name ?? 'Sin contacto',
                'client_document' => $document->contact->tax_id ?? $document->contact->document_number ?? 'Sin documento',
                'client_avatar' => strtoupper(substr($document->contact->name ?? 'SC', 0, 1)),
                'date' => $document->created_at->format('d M Y'),
            ],
            'items' => $items,
        ]);
    }

    /**
     * FLUJO 1: Agregar múltiples items de cotización a orden
     */
    public function addItemsToOrder(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:document_items,id',
        ]);

        $companyId = auth()->user()->company_id;

        DB::transaction(function () use ($request, $purchaseOrder, $companyId) {
            foreach ($request->item_ids as $itemId) {
                $item = DocumentItem::where('id', $itemId)
                    ->where('company_id', $companyId)
                    ->firstOrFail();

                // Verificar si ya está en esta orden
                if ($item->isInPurchaseOrder($purchaseOrder)) {
                    continue;
                }

                // Calcular precios
                $unitPrice = $this->calculateUnitPrice($item);
                $totalPrice = $this->calculateTotalPrice($item);

                // Agregar a la orden (pivot table)
                $purchaseOrder->documentItems()->attach($item->id, [
                    'quantity_ordered' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                ]);

                // Actualizar order_status del item
                $item->updateOrderStatus();
            }

            // Recalcular total de la orden
            $purchaseOrder->recalculateTotal();
        });

        return response()->json([
            'success' => true,
            'message' => 'Items agregados exitosamente',
        ]);
    }

    /**
     * Calcular precio unitario según tipo de item
     */
    private function calculateUnitPrice(DocumentItem $item): float
    {
        if ($item->itemable_type === 'App\Models\SimpleItem' && $item->itemable) {
            $paper = $item->itemable->paper;
            return $paper ? ($paper->cost_per_sheet ?? 0) : 0;
        } elseif ($item->itemable_type === 'App\Models\Product' && $item->itemable) {
            return $item->itemable->sale_price ?? 0;
        }

        return $item->unit_price ?? 0;
    }

    /**
     * Calcular precio total según tipo de item
     */
    private function calculateTotalPrice(DocumentItem $item): float
    {
        if ($item->itemable_type === 'App\Models\SimpleItem' && $item->itemable) {
            $sheets = $item->itemable->total_sheets ?? 0;
            return $sheets * $this->calculateUnitPrice($item);
        }

        return $item->quantity * $this->calculateUnitPrice($item);
    }

    /**
     * Obtener información detallada del itemable
     */
    private function getItemableInfo(DocumentItem $item): array
    {
        if (!$item->itemable) {
            return [];
        }

        if ($item->itemable_type === 'App\Models\SimpleItem') {
            return [
                'type' => 'papel',
                'paper_type' => $item->itemable->paper->name ?? 'N/A',
                'sheets_needed' => $item->itemable->total_sheets ?? 0,
                'cut_size' => "{$item->itemable->horizontal_size}x{$item->itemable->vertical_size}cm",
            ];
        } elseif ($item->itemable_type === 'App\Models\Product') {
            return [
                'type' => 'producto',
                'product_name' => $item->itemable->name,
                'product_code' => $item->itemable->code ?? 'N/A',
                'stock' => $item->itemable->stock ?? 0,
            ];
        }

        return [];
    }
}

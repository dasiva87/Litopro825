<?php

namespace App\Http\Controllers;

use App\Models\DocumentItem;
use App\Models\PurchaseOrder;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentItemController extends Controller
{
    /**
     * FLUJO 2: Obtener órdenes abiertas para agregar un item desde cotización
     */
    public function getOpenOrders(Request $request)
    {
        $companyId = TenantContext::id();

        $orders = PurchaseOrder::forTenant($companyId)
            ->whereIn('status', ['draft', 'sent', 'confirmed']) // Solo órdenes abiertas
            ->with('supplierCompany')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'supplier' => $order->supplierCompany->name,
                    'status' => $order->status,
                    'status_label' => $order->getStatusLabelAttribute(),
                    'total_amount' => number_format($order->total_amount, 2),
                    'items_count' => $order->documentItems()->count(),
                    'created_at' => $order->created_at->format('d M Y'),
                ];
            });

        return response()->json($orders);
    }

    /**
     * FLUJO 2: Agregar un item específico a una o varias órdenes desde cotización
     */
    public function addToOrders(Request $request, DocumentItem $documentItem)
    {
        $companyId = TenantContext::id();

        // Verificar que el item pertenece a la empresa del usuario
        if ($documentItem->company_id !== $companyId) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para acceder a este item',
            ], 403);
        }

        $validated = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:purchase_orders,id',
        ], [
            'order_ids.required' => 'Debe seleccionar al menos una orden',
            'order_ids.*.exists' => 'Una o más órdenes seleccionadas no son válidas',
        ]);

        DB::transaction(function () use ($validated, $documentItem, $companyId) {
            foreach ($validated['order_ids'] as $orderId) {
                $order = PurchaseOrder::forTenant($companyId)
                    ->where('id', $orderId)
                    ->firstOrFail();

                // Verificar si ya está en esta orden
                if ($documentItem->isInPurchaseOrder($order)) {
                    continue; // Skip si ya existe
                }

                // Calcular precios
                $unitPrice = $this->calculateUnitPrice($documentItem);
                $totalPrice = $this->calculateTotalPrice($documentItem);

                // Agregar a la orden
                $order->documentItems()->attach($documentItem->id, [
                    'quantity_ordered' => $documentItem->quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                ]);

                // Recalcular total
                $order->recalculateTotal();
            }

            // Actualizar order_status del item
            $documentItem->updateOrderStatus();
        });

        return response()->json([
            'success' => true,
            'message' => 'Item agregado a las órdenes seleccionadas',
        ]);
    }

    /**
     * Calcular precio unitario según tipo de item
     */
    private function calculateUnitPrice(DocumentItem $item): float
    {
        if ($item->itemable_type === 'App\Models\SimpleItem' && $item->itemable) {
            $paper = $item->itemable->paper;
            return $paper ? ($paper->price ?? $paper->cost_per_sheet ?? 0) : 0;
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
}

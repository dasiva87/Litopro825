<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\DocumentItem;
use Illuminate\Support\Collection;

class ProductionOrderGroupingService
{
    /**
     * Agrupa items y sus acabados por proveedor
     *
     * @param Collection $documentItems
     * @return array ['supplier_id' => ['printing' => [...], 'finishings' => [...]]]
     */
    public function groupBySupplier(Collection $documentItems): array
    {
        $grouped = [];

        foreach ($documentItems as $item) {
            // 1. Procesar impresión (si es SimpleItem y tiene printing_machine)
            if ($item->itemable_type === 'App\Models\SimpleItem' && $item->itemable) {
                $printingSupplierId = $this->getPrintingSupplier($item);

                if ($printingSupplierId) {
                    if (!isset($grouped[$printingSupplierId])) {
                        $grouped[$printingSupplierId] = [
                            'printing' => [],
                            'finishings' => [],
                        ];
                    }

                    $grouped[$printingSupplierId]['printing'][] = [
                        'document_item' => $item,
                        'quantity' => $item->quantity,
                        'process_type' => 'printing',
                        'process_description' => "Impresión: {$item->description}",
                    ];
                }
            }

            // 2. Procesar acabados del item
            $finishings = $item->finishings()->with('supplier')->get();

            foreach ($finishings as $finishing) {
                $finishingSupplierId = $finishing->supplier_id;

                if (!$finishingSupplierId) {
                    continue; // Skip finishings sin proveedor
                }

                if (!isset($grouped[$finishingSupplierId])) {
                    $grouped[$finishingSupplierId] = [
                        'printing' => [],
                        'finishings' => [],
                    ];
                }

                $grouped[$finishingSupplierId]['finishings'][] = [
                    'document_item' => $item,
                    'finishing' => $finishing,
                    'quantity' => $finishing->quantity,
                    'process_type' => 'finishing',
                    'finishing_name' => $finishing->finishing_name,
                    'process_description' => "Acabado {$finishing->finishing_name}: {$item->description}",
                ];
            }
        }

        return $grouped;
    }

    /**
     * Obtiene el proveedor de impresión para un item
     * Por ahora retorna null, ya que SimpleItem no tiene supplier directo
     * En el futuro se podría inferir desde PrintingMachine o asignar manualmente
     */
    protected function getPrintingSupplier(DocumentItem $item): ?int
    {
        // TODO: Implementar lógica para obtener proveedor de impresión
        // Opción 1: Si PrintingMachine tiene supplier_id
        // Opción 2: Si SimpleItem tiene supplier_id
        // Opción 3: Usuario selecciona manualmente

        // Por ahora retornamos null para que solo se procesen acabados
        // La impresión se manejará después cuando se defina la arquitectura
        return null;
    }

    /**
     * Cuenta cuántas órdenes se crearán
     */
    public function countOrders(Collection $documentItems): int
    {
        $grouped = $this->groupBySupplier($documentItems);
        return count($grouped);
    }

    /**
     * Genera un resumen de las órdenes que se crearán
     */
    public function getOrdersSummary(Collection $documentItems): array
    {
        $grouped = $this->groupBySupplier($documentItems);
        $summary = [];

        foreach ($grouped as $supplierId => $processes) {
            $supplier = Contact::find($supplierId);
            $totalProcesses = count($processes['printing']) + count($processes['finishings']);

            $summary[] = [
                'supplier_id' => $supplierId,
                'supplier_name' => $supplier?->name ?? 'Desconocido',
                'printing_count' => count($processes['printing']),
                'finishing_count' => count($processes['finishings']),
                'total_processes' => $totalProcesses,
            ];
        }

        return $summary;
    }
}

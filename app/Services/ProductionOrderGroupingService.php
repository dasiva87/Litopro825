<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\DocumentItem;
use Illuminate\Support\Collection;

class ProductionOrderGroupingService
{
    /**
     * Agrupa items para producción
     * IMPORTANTE: Se enfoca en IMPRESIÓN (millares), no en acabados
     * Los acabados son opcionales y se agregan si tienen proveedor asignado
     *
     * @param Collection $documentItems
     * @return array ['internal' => ['printing' => [...], 'finishings' => [...]]] o ['supplier_id' => [...]]
     */
    public function groupBySupplier(Collection $documentItems): array
    {
        $grouped = [];

        foreach ($documentItems as $item) {
            // 1. SIEMPRE procesar impresión para items con millares (producción interna)
            // Esto es lo MÁS IMPORTANTE de las órdenes de producción
            if ($this->hasImpression($item)) {
                $supplierId = 'internal'; // Producción interna por defecto

                if (!isset($grouped[$supplierId])) {
                    $grouped[$supplierId] = [
                        'printing' => [],
                        'finishings' => [],
                        'is_internal' => true,
                    ];
                }

                $grouped[$supplierId]['printing'][] = [
                    'document_item' => $item,
                    'quantity' => $item->quantity,
                    'process_type' => 'printing',
                    'process_description' => "Impresión: {$item->description}",
                ];
            }

            // 2. OPCIONALMENTE procesar acabados (solo si tienen proveedor asignado)
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
                        'is_internal' => false,
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
     * Verifica si un item tiene impresiones (millares)
     */
    protected function hasImpression(DocumentItem $item): bool
    {
        // Si tiene sheets_needed (pliegos), entonces tiene impresión
        if (isset($item->sheets_needed) && $item->sheets_needed > 0) {
            return true;
        }

        // Verificar en el itemable si existe
        if ($item->itemable) {
            // SimpleItem, DigitalItem tienen total_impressions
            if (isset($item->itemable->total_impressions) && $item->itemable->total_impressions > 0) {
                return true;
            }

            // MagazineItem tiene sheets_needed
            if (isset($item->itemable->sheets_needed) && $item->itemable->sheets_needed > 0) {
                return true;
            }
        }

        // Si tiene máquina de impresión asignada, asumimos que hay impresión
        if ($item->printing_machine_id) {
            return true;
        }

        return false;
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
            $supplierName = 'Producción Interna';

            if ($supplierId !== 'internal') {
                $supplier = Contact::find($supplierId);
                $supplierName = $supplier?->name ?? 'Desconocido';
            }

            $totalProcesses = count($processes['printing']) + count($processes['finishings']);

            $summary[] = [
                'supplier_id' => $supplierId,
                'supplier_name' => $supplierName,
                'printing_count' => count($processes['printing']),
                'finishing_count' => count($processes['finishings']),
                'total_processes' => $totalProcesses,
            ];
        }

        return $summary;
    }
}

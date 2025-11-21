<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\DocumentItem;
use App\Models\Product;
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

                    // Construir descripción detallada de impresión
                    $simpleItem = $item->itemable;
                    $description = $this->buildPrintingDescription($item, $simpleItem);

                    $grouped[$printingSupplierId]['printing'][] = [
                        'document_item' => $item,
                        'quantity' => $item->quantity,
                        'process_type' => 'printing',
                        'process_description' => $description,
                    ];
                }
            }

            // 1.1. Procesar producción digital (si es DigitalItem)
            if ($item->itemable_type === 'App\Models\DigitalItem' && $item->itemable) {
                $digitalSupplierId = $this->getDigitalSupplier($item);

                if ($digitalSupplierId) {
                    if (!isset($grouped[$digitalSupplierId])) {
                        $grouped[$digitalSupplierId] = [
                            'printing' => [],
                            'finishings' => [],
                        ];
                    }

                    // Construir descripción detallada de producción digital
                    $digitalItem = $item->itemable;
                    $description = $this->buildDigitalDescription($item, $digitalItem);

                    $grouped[$digitalSupplierId]['printing'][] = [
                        'document_item' => $item,
                        'quantity' => $item->quantity,
                        'process_type' => 'printing', // Usar 'printing' para producción digital también
                        'process_description' => $description,
                    ];
                }
            }

            // NOTA: Products NO generan proceso de printing/digital
            // Solo generan procesos de acabados (ver sección 2 abajo)

            // 2. Procesar acabados del item (desde itemable: SimpleItem, DigitalItem, Product, etc.)
            // Obtener acabados según el tipo de item
            $finishings = collect([]);

            // Para Products: los acabados están en item_config (JSON)
            if ($item->itemable_type === 'App\Models\Product' && !empty($item->item_config['finishings'])) {
                $finishingsConfig = $item->item_config['finishings'] ?? [];
                
                foreach ($finishingsConfig as $finishingConfig) {
                    $finishing = \App\Models\Finishing::find($finishingConfig['finishing_id']);
                    if ($finishing) {
                        // Simular pivot data para compatibilidad
                        $finishing->pivot = (object) [
                            'quantity' => $finishingConfig['quantity'] ?? 0,
                            'width' => $finishingConfig['width'] ?? null,
                            'height' => $finishingConfig['height'] ?? null,
                        ];
                        $finishings->push($finishing);
                    }
                }
            }
            // Para SimpleItems/DigitalItems: los acabados están en tabla pivot
            else {
                // Cargar itemable con sus acabados
                if (!$item->relationLoaded('itemable')) {
                    $item->load('itemable.finishings');
                }

                $itemable = $item->itemable;

                // Verificar que itemable tenga la relación finishings
                if ($itemable && method_exists($itemable, 'finishings')) {
                    if (!$itemable->relationLoaded('finishings')) {
                        $itemable->load('finishings');
                    }
                    $finishings = $itemable->finishings ?? collect([]);
                }
            }

            foreach ($finishings as $finishing) {
                // El supplier_id viene del modelo Finishing (no del pivot)
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

                // Construir descripción con cantidad y unidad del pivot
                $pivotQuantity = $finishing->pivot->quantity ?? 0;
                $pivotWidth = $finishing->pivot->width ?? null;
                $pivotHeight = $finishing->pivot->height ?? null;

                $quantityText = $this->formatFinishingQuantityFromPivot($finishing);
                $description = "Acabado {$finishing->name}: {$item->description} ({$quantityText})";

                $grouped[$finishingSupplierId]['finishings'][] = [
                    'document_item' => $item,
                    'finishing' => $finishing,
                    'quantity' => $pivotQuantity,
                    'process_type' => 'finishing',
                    'finishing_name' => $finishing->name,
                    'process_description' => $description,
                    // Parámetros adicionales para la orden de producción
                    'finishing_parameters' => [
                        'document_item_finishing_id' => null, // Ya no usamos document_item_finishings
                        'finishing_quantity' => $pivotQuantity,
                        'finishing_width' => $pivotWidth,
                        'finishing_height' => $pivotHeight,
                        'finishing_unit' => $finishing->measurement_unit->value ?? 'unidad',
                    ],
                ];
            }
        }

        return $grouped;
    }

    /**
     * Obtiene el proveedor para un DigitalItem
     * Extrae el supplier_id desde DigitalItem.supplier_contact_id o usa autorreferencial si is_own_product
     */
    protected function getDigitalSupplier(DocumentItem $item): ?int
    {
        // Cargar itemable si no está cargado
        if (!$item->relationLoaded('itemable')) {
            $item->load('itemable');
        }

        $digitalItem = $item->itemable;

        if (!$digitalItem || !($digitalItem instanceof \App\Models\DigitalItem)) {
            return null;
        }

        // Si es producto propio (is_own_product = true), asignar contacto autorreferencial
        if ($digitalItem->is_own_product) {
            return $this->getSelfContactId($item->company_id);
        }

        // Si es producto externo, usar su supplier_contact_id
        return $digitalItem->supplier_contact_id;
    }

    /**
     * Obtiene el proveedor de impresión para un item
     * Extrae el supplier_id desde PrintingMachine
     */
    protected function getPrintingSupplier(DocumentItem $item): ?int
    {
        // Cargar itemable con printing_machine si no está cargado
        if (!$item->relationLoaded('itemable')) {
            $item->load('itemable.printingMachine');
        }

        $itemable = $item->itemable;

        // Verificar que sea SimpleItem y tenga printing_machine
        if (!$itemable || !method_exists($itemable, 'printingMachine')) {
            return null;
        }

        // Cargar printingMachine si no está cargado
        if (!$itemable->relationLoaded('printingMachine')) {
            $itemable->load('printingMachine');
        }

        $printingMachine = $itemable->printingMachine;

        if (!$printingMachine) {
            return null;
        }

        // Si la máquina es propia (is_own = true), asignar contacto autorreferencial
        if ($printingMachine->is_own) {
            return $this->getSelfContactId($item->company_id);
        }

        // Si la máquina es externa, usar su supplier_id
        return $printingMachine->supplier_id;
    }

    /**
     * Obtiene o crea el contacto autorreferencial para producción propia
     * Reutiliza la misma lógica que Finishing
     */
    protected function getSelfContactId(int $companyId): ?int
    {
        $company = \App\Models\Company::find($companyId);
        if (!$company) {
            return null;
        }

        $selfContact = \App\Models\Contact::where('company_id', $companyId)
            ->where('name', 'LIKE', $company->name . ' (Producción Propia)')
            ->first();

        if (!$selfContact) {
            $selfContact = \App\Models\Contact::create([
                'company_id' => $companyId,
                'name' => $company->name . ' (Producción Propia)',
                'email' => 'produccion@' . strtolower(str_replace(' ', '', $company->name)) . '.com',
            ]);
        }

        return $selfContact->id;
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

    /**
     * Construye la descripción detallada para un proceso de impresión
     */
    protected function buildPrintingDescription(DocumentItem $documentItem, $simpleItem): string
    {
        $parts = [];

        // Descripción base
        $parts[] = "Impresión: {$documentItem->description}";

        // Cantidad
        $quantity = number_format($documentItem->quantity, 0);
        $parts[] = "({$quantity} unidades)";

        // Tamaño
        if ($simpleItem->horizontal_size && $simpleItem->vertical_size) {
            $parts[] = "{$simpleItem->horizontal_size}x{$simpleItem->vertical_size} cm";
        }

        // Tintas
        $inkFront = $simpleItem->ink_front_count ?? 0;
        $inkBack = $simpleItem->ink_back_count ?? 0;
        $parts[] = "Tintas {$inkFront}x{$inkBack}";

        // Papel
        if ($simpleItem->paper) {
            $parts[] = "en {$simpleItem->paper->name}";
        }

        return implode(' ', $parts);
    }

    /**
     * Construye la descripción detallada para un proceso digital
     */
    protected function buildDigitalDescription(DocumentItem $documentItem, $digitalItem): string
    {
        $parts = [];

        // Descripción base
        $parts[] = "Producción Digital: {$documentItem->description}";

        // Cantidad
        $quantity = number_format($documentItem->quantity, 0);
        $parts[] = "({$quantity} unidades)";

        // Tipo de producto (si está en metadata)
        if ($digitalItem->metadata && isset($digitalItem->metadata['product_type'])) {
            $parts[] = "Tipo: {$digitalItem->metadata['product_type']}";
        }

        return implode(' ', $parts);
    }

    /**
     * Formatea la cantidad de un acabado según sus parámetros del pivot
     */
    protected function formatFinishingQuantityFromPivot($finishing): string
    {
        $quantity = $finishing->pivot->quantity ?? 0;
        $width = $finishing->pivot->width ?? null;
        $height = $finishing->pivot->height ?? null;
        $unit = $finishing->measurement_unit->value ?? 'unidad';

        // Formatear según tipo de medida
        if ($unit === 'tamaño' && $width && $height) {
            $area = ($width * $height) / 10000; // Convertir cm² a m²
            return number_format($area, 4) . ' m² (' . $width . 'x' . $height . ' cm)';
        }

        if ($unit === 'millar') {
            return number_format($quantity, 2) . ' millares';
        }

        return number_format($quantity, 2) . ' ' . $unit;
    }
}

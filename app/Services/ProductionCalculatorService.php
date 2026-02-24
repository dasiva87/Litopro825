<?php

namespace App\Services;

use App\Models\DocumentItem;
use App\Models\SimpleItem;
use App\Models\DigitalItem;

class ProductionCalculatorService
{
    /**
     * Calculate production data from a DocumentItem
     *
     * @param DocumentItem $documentItem
     * @param int $quantityToProduce Quantity to produce (can be different from item quantity)
     * @return array Production data ready for pivot table
     */
    public function calculateProductionData(DocumentItem $documentItem, ?int $quantityToProduce = null): array
    {
        // Get the itemable (SimpleItem, DigitalItem, etc.)
        $itemable = $documentItem->itemable;

        // If no quantity specified, use the document item quantity
        $quantityToProduce = $quantityToProduce ?? (int) $documentItem->quantity;

        // Different calculation based on item type
        if ($itemable instanceof SimpleItem) {
            return $this->calculateFromSimpleItem($itemable, $quantityToProduce, $documentItem);
        }

        if ($itemable instanceof DigitalItem) {
            return $this->calculateFromDigitalItem($itemable, $quantityToProduce, $documentItem);
        }

        if ($itemable instanceof \App\Models\Product) {
            return $this->calculateFromProduct($itemable, $quantityToProduce, $documentItem);
        }

        // Default fallback for other item types
        return $this->calculateDefault($documentItem, $quantityToProduce);
    }

    /**
     * Calculate production data from SimpleItem
     */
    protected function calculateFromSimpleItem(SimpleItem $simpleItem, int $quantityToProduce, DocumentItem $documentItem): array
    {
        // Calculate sheets needed based on quantity to produce
        $sheetsNeeded = $this->calculateSheetsNeeded($simpleItem, $quantityToProduce);

        // Calculate total impressions (millares)
        $totalImpressions = $this->calculateTotalImpressions(
            $sheetsNeeded,
            $simpleItem->ink_front_count ?? 0,
            $simpleItem->ink_back_count ?? 0,
            $simpleItem->front_back_plate ?? false
        );

        // Determinar tamaño de corte (HOJA) según mounting_type
        if ($simpleItem->mounting_type === 'custom') {
            // Usar dimensiones personalizadas de la hoja
            $cutWidth = $simpleItem->custom_paper_width;
            $cutHeight = $simpleItem->custom_paper_height;
        } else {
            // Usar dimensiones de la máquina (automático)
            $cutWidth = $simpleItem->printingMachine?->max_width ?? $simpleItem->horizontal_size;
            $cutHeight = $simpleItem->printingMachine?->max_height ?? $simpleItem->vertical_size;
        }

        return [
            'quantity_to_produce' => $quantityToProduce,
            'sheets_needed' => $sheetsNeeded,
            'total_impressions' => $totalImpressions,
            'ink_front_count' => $simpleItem->ink_front_count ?? 0,
            'ink_back_count' => $simpleItem->ink_back_count ?? 0,
            'front_back_plate' => $simpleItem->front_back_plate ?? false,
            'paper_id' => $simpleItem->paper_id,
            'horizontal_size' => $simpleItem->horizontal_size,
            'vertical_size' => $simpleItem->vertical_size,
            'cut_width' => $cutWidth,
            'cut_height' => $cutHeight,
            'produced_quantity' => 0,
            'rejected_quantity' => 0,
            'item_status' => 'pending',
        ];
    }

    /**
     * Calculate production data from DigitalItem
     * Digital items don't require sheets/impressions calculation
     */
    protected function calculateFromDigitalItem(DigitalItem $digitalItem, int $quantityToProduce, DocumentItem $documentItem): array
    {
        return [
            'quantity_to_produce' => $quantityToProduce,
            'sheets_needed' => 0, // No aplica para items digitales
            'total_impressions' => 0, // No aplica para items digitales
            'ink_front_count' => 0,
            'ink_back_count' => 0,
            'front_back_plate' => false,
            'paper_id' => null,
            'horizontal_size' => $documentItem->width ?? 0,
            'vertical_size' => $documentItem->height ?? 0,
            'produced_quantity' => 0,
            'rejected_quantity' => 0,
            'item_status' => 'pending',
        ];
    }

    /**
     * Calculate default production data for non-SimpleItem types
     */
    protected function calculateDefault(DocumentItem $documentItem, int $quantityToProduce): array
    {
        return [
            'quantity_to_produce' => $quantityToProduce,
            'sheets_needed' => 0,
            'total_impressions' => 0,
            'ink_front_count' => $documentItem->colors_front ?? 0,
            'ink_back_count' => $documentItem->colors_back ?? 0,
            'front_back_plate' => false,
            'paper_id' => $documentItem->paper_id,
            'horizontal_size' => $documentItem->width,
            'vertical_size' => $documentItem->height,
            'produced_quantity' => 0,
            'rejected_quantity' => 0,
            'item_status' => 'pending',
        ];
    }

    /**
     * Calculate sheets needed based on quantity to produce
     * Uses the same logic as SimpleItem::calculateMounting()
     */
    protected function calculateSheetsNeeded(SimpleItem $simpleItem, int $quantityToProduce): int
    {
        if (!$simpleItem->paper || !$simpleItem->horizontal_size || !$simpleItem->vertical_size) {
            return 0;
        }

        // Calculate cuts per sheet
        $cuts = $this->calculateCutsPerSheet(
            $simpleItem->paper->width,
            $simpleItem->paper->height,
            $simpleItem->horizontal_size,
            $simpleItem->vertical_size
        );

        if ($cuts === 0) {
            return 0;
        }

        // Calculate sheets needed considering sobrante (waste)
        $baseSheets = (int) ceil($quantityToProduce / $cuts);
        $sobrante = $simpleItem->sobrante_papel ?? 0;

        return $baseSheets + $sobrante;
    }

    /**
     * Calculate how many cuts fit per sheet
     * Tests both orientations and returns the best one
     */
    protected function calculateCutsPerSheet(
        float $paperWidth,
        float $paperHeight,
        float $cutWidth,
        float $cutHeight
    ): int {
        // Normal orientation
        $cutsH = floor($paperWidth / $cutWidth);
        $cutsV = floor($paperHeight / $cutHeight);
        $totalNormal = $cutsH * $cutsV;

        // Rotated orientation
        $cutsH_rotated = floor($paperWidth / $cutHeight);
        $cutsV_rotated = floor($paperHeight / $cutWidth);
        $totalRotated = $cutsH_rotated * $cutsV_rotated;

        // Return the best option
        return max($totalNormal, $totalRotated);
    }

    /**
     * Calculate total impressions (millares)
     * Formula: sheets × total_inks
     *
     * If front_back_plate is true, only count the highest ink count (front or back)
     * Otherwise, sum both front and back inks
     */
    public function calculateTotalImpressions(
        int $sheets,
        int $inkFrontCount,
        int $inkBackCount,
        bool $frontBackPlate
    ): float {
        if ($frontBackPlate) {
            // Tiro y retiro plancha: solo se cuenta la mayor cantidad de tintas
            $totalInks = max($inkFrontCount, $inkBackCount);
        } else {
            // Plancha separada: se suman ambas
            $totalInks = $inkFrontCount + $inkBackCount;
        }

        // Return impressions (sheets × inks)
        // Convert to "millares" (thousands) - divide by 1000
        return ($sheets * $totalInks) / 1000;
    }

    /**
     * Validate if a DocumentItem can be added to production
     */
    public function canBeProduced(DocumentItem $documentItem): array
    {
        $errors = [];

        // Check if itemable exists
        if (!$documentItem->itemable) {
            $errors[] = 'El item no tiene datos asociados (itemable no encontrado)';
            return ['valid' => false, 'errors' => $errors];
        }

        $itemable = $documentItem->itemable;

        // Validate based on item type
        if ($itemable instanceof SimpleItem) {
            return $this->validateSimpleItem($itemable, $documentItem);
        }

        if ($itemable instanceof DigitalItem) {
            return $this->validateDigitalItem($itemable, $documentItem);
        }

        if ($itemable instanceof \App\Models\Product) {
            return $this->validateProduct($itemable, $documentItem);
        }

        // Other item types not supported
        $errors[] = 'Solo items tipo "SimpleItem", "DigitalItem" o "Product" pueden enviarse a producción';
        return ['valid' => false, 'errors' => $errors];
    }

    /**
     * Validate SimpleItem for production
     */
    protected function validateSimpleItem(SimpleItem $simpleItem, DocumentItem $documentItem): array
    {
        $errors = [];

        // Validate required fields
        if (!$simpleItem->paper_id) {
            $errors[] = 'El item no tiene papel asignado';
        }

        if (!$simpleItem->horizontal_size || !$simpleItem->vertical_size) {
            $errors[] = 'El item no tiene dimensiones definidas';
        }

        if (!$simpleItem->printing_machine_id) {
            $errors[] = 'El item no tiene máquina de impresión asignada';
        }

        if (($simpleItem->ink_front_count ?? 0) === 0 && ($simpleItem->ink_back_count ?? 0) === 0) {
            $errors[] = 'El item no tiene tintas definidas (frente o reverso)';
        }

        if ($documentItem->quantity <= 0) {
            $errors[] = 'El item no tiene cantidad válida';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate DigitalItem for production
     */
    protected function validateDigitalItem(DigitalItem $digitalItem, DocumentItem $documentItem): array
    {
        $errors = [];

        // Validate required fields for digital items
        if (!$digitalItem->supplier_contact_id && !$digitalItem->is_own_product) {
            $errors[] = 'El item digital no tiene proveedor asignado';
        }

        if ($documentItem->quantity <= 0) {
            $errors[] = 'El item no tiene cantidad válida';
        }

        // Digital items are always valid if they have supplier and quantity
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get production summary for multiple items
     */
    public function getProductionSummary(array $documentItems): array
    {
        $totalSheets = 0;
        $totalImpressions = 0;
        $paperTypes = [];
        $machines = [];

        foreach ($documentItems as $item) {
            if (!$item instanceof DocumentItem) {
                continue;
            }

            $productionData = $this->calculateProductionData($item);

            $totalSheets += $productionData['sheets_needed'];
            $totalImpressions += $productionData['total_impressions'];

            if ($productionData['paper_id']) {
                $paperTypes[$productionData['paper_id']] = true;
            }

            if ($item->itemable && $item->itemable->printing_machine_id) {
                $machines[$item->itemable->printing_machine_id] = true;
            }
        }

        return [
            'total_items' => count($documentItems),
            'total_sheets' => $totalSheets,
            'total_impressions' => round($totalImpressions, 2),
            'different_papers' => count($paperTypes),
            'different_machines' => count($machines),
        ];
    }

    /**
     * Validate Product for production
     */
    protected function validateProduct(\App\Models\Product $product, DocumentItem $documentItem): array
    {
        $errors = [];

        // Products only need acabados (finishings) to be valid for production
        // Check if has acabados in item_config
        if (empty($documentItem->item_config['finishings'])) {
            $errors[] = 'El producto no tiene acabados definidos para producción';
        }

        if ($documentItem->quantity <= 0) {
            $errors[] = 'El producto no tiene cantidad válida';
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
        ];
    }

    /**
     * Calculate production data from Product
     */
    protected function calculateFromProduct(\App\Models\Product $product, int $quantityToProduce, DocumentItem $documentItem): array
    {
        // Products don't have printing process, only finishing processes
        // Return minimal data structure
        return [
            'quantity_to_produce' => $quantityToProduce,
            'sheets_needed' => 0, // No sheets needed for products
            'total_impressions' => 0, // No impressions for products
            'ink_front_count' => 0,
            'ink_back_count' => 0,
            'front_back_plate' => false,
            'paper_id' => null, // Products don't use paper directly
            'horizontal_size' => null,
            'vertical_size' => null,
            'produced_quantity' => 0,
            'rejected_quantity' => 0,
            'item_status' => 'pending',
        ];
    }
}

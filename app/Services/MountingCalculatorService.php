<?php

namespace App\Services;

/**
 * Servicio para cálculo de montaje (cuántos TRABAJOS caben en una HOJA)
 *
 * TERMINOLOGÍA:
 * - PLIEGO (100×70cm): Papel del proveedor (tamaño original)
 * - HOJA (ej: 50×35cm): Corte del pliego que va a la máquina de impresión
 * - TRABAJO (ej: 10×15cm): Producto final (volante, tarjeta, etc.)
 *
 * Este servicio calcula cuántos TRABAJOS caben en una HOJA,
 * considerando márgenes/sangría obligatorios.
 */
class MountingCalculatorService
{
    /**
     * Calcula cuántos TRABAJOS caben en una HOJA
     *
     * @param float $workWidth Ancho del TRABAJO (producto final) en cm
     * @param float $workHeight Alto del TRABAJO (producto final) en cm
     * @param float $machineWidth Ancho de la HOJA (tamaño máquina) en cm
     * @param float $machineHeight Alto de la HOJA (tamaño máquina) en cm
     * @param float $marginPerSide Margen/sangría por cada lado en cm (default: 1cm)
     *
     * @return array [
     *   'horizontal' => ['copies_per_sheet', 'layout', 'orientation', 'rows', 'cols'],
     *   'vertical' => ['copies_per_sheet', 'layout', 'orientation', 'rows', 'cols'],
     *   'maximum' => ['copies_per_sheet', 'layout', 'orientation', 'rows', 'cols']
     * ]
     */
    public function calculateMounting(
        float $workWidth,
        float $workHeight,
        float $machineWidth,
        float $machineHeight,
        float $marginPerSide = 1.0
    ): array {
        // Calcular área útil restando márgenes (margen en cada lado × 2)
        $usableWidth = $machineWidth - ($marginPerSide * 2);
        $usableHeight = $machineHeight - ($marginPerSide * 2);

        // Validar que el área útil sea positiva
        if ($usableWidth <= 0 || $usableHeight <= 0) {
            return $this->emptyResult();
        }

        // Validar que el trabajo no sea más grande que el área útil
        if ($workWidth > $usableWidth || $workHeight > $usableHeight) {
            // Verificar si cabe rotado
            if ($workWidth > $usableHeight || $workHeight > $usableWidth) {
                return $this->emptyResult(); // No cabe de ninguna manera
            }
        }

        // ORIENTACIÓN HORIZONTAL: Trabajo en su orientación original
        $horizontal = $this->calculateOrientation(
            $workWidth,
            $workHeight,
            $usableWidth,
            $usableHeight,
            'horizontal'
        );

        // ORIENTACIÓN VERTICAL: Trabajo rotado 90°
        $vertical = $this->calculateOrientation(
            $workHeight,  // Intercambiar dimensiones
            $workWidth,   // para simular rotación
            $usableWidth,
            $usableHeight,
            'vertical'
        );

        // MÁXIMO: La orientación que permita más copias
        $maximum = $horizontal['copies_per_sheet'] >= $vertical['copies_per_sheet']
            ? $horizontal
            : $vertical;

        return [
            'horizontal' => $horizontal,
            'vertical' => $vertical,
            'maximum' => $maximum,
        ];
    }

    /**
     * Calcula cuántos TRABAJOS caben en una orientación específica
     *
     * @param float $workW Ancho del TRABAJO (puede estar rotado)
     * @param float $workH Alto del TRABAJO (puede estar rotado)
     * @param float $usableW Ancho útil de la HOJA
     * @param float $usableH Alto útil de la HOJA
     * @param string $orientation Nombre de la orientación ('horizontal' o 'vertical')
     *
     * @return array
     */
    private function calculateOrientation(
        float $workW,
        float $workH,
        float $usableW,
        float $usableH,
        string $orientation
    ): array {
        // Calcular cuántos TRABAJOS caben horizontalmente en la HOJA
        $copiesHorizontal = (int) floor($usableW / $workW);

        // Calcular cuántos TRABAJOS caben verticalmente en la HOJA
        $copiesVertical = (int) floor($usableH / $workH);

        // Total de TRABAJOS por HOJA
        $copiesPerSheet = $copiesHorizontal * $copiesVertical;

        // Formato del layout (ej: "2 × 3" = 2 columnas × 3 filas)
        $layout = $copiesHorizontal > 0 && $copiesVertical > 0
            ? "{$copiesHorizontal} × {$copiesVertical}"
            : '0 × 0';

        return [
            'copies_per_sheet' => $copiesPerSheet,
            'layout' => $layout,
            'orientation' => $orientation,
            'rows' => $copiesVertical,
            'cols' => $copiesHorizontal,
            'work_width' => round($workW, 2),
            'work_height' => round($workH, 2),
        ];
    }

    /**
     * Resultado vacío cuando no caben copias
     *
     * @return array
     */
    private function emptyResult(): array
    {
        $empty = [
            'copies_per_sheet' => 0,
            'layout' => '0 × 0',
            'orientation' => 'none',
            'rows' => 0,
            'cols' => 0,
            'work_width' => 0,
            'work_height' => 0,
        ];

        return [
            'horizontal' => $empty,
            'vertical' => $empty,
            'maximum' => $empty,
        ];
    }

    /**
     * Calcula HOJAS necesarias basándose en cantidad de TRABAJOS requeridos
     *
     * NOTA: Este método calcula cuántas HOJAS se necesitan para producir
     * la cantidad de TRABAJOS solicitados. Las HOJAS salen de cortar PLIEGOS.
     *
     * @param int $requiredCopies Cantidad de TRABAJOS que el cliente necesita
     * @param int $copiesPerSheet TRABAJOS que caben por HOJA (del método calculateMounting)
     *
     * @return array [
     *   'sheets_needed' => int (HOJAS necesarias),
     *   'total_copies_produced' => int (TRABAJOS producidos),
     *   'waste_copies' => int (TRABAJOS de desperdicio)
     * ]
     */
    public function calculateRequiredSheets(int $requiredCopies, int $copiesPerSheet): array
    {
        if ($copiesPerSheet <= 0) {
            return [
                'sheets_needed' => 0,
                'total_copies_produced' => 0,
                'waste_copies' => 0,
            ];
        }

        // HOJAS necesarias (redondear hacia arriba)
        $sheetsNeeded = (int) ceil($requiredCopies / $copiesPerSheet);

        // Total de TRABAJOS producidos
        $totalCopiesProduced = $sheetsNeeded * $copiesPerSheet;

        // TRABAJOS de desperdicio
        $wasteCopies = $totalCopiesProduced - $requiredCopies;

        return [
            'sheets_needed' => $sheetsNeeded,
            'total_copies_produced' => $totalCopiesProduced,
            'waste_copies' => $wasteCopies,
        ];
    }

    /**
     * Calcula el aprovechamiento de la HOJA (porcentaje de área utilizada)
     *
     * @param float $workWidth Ancho del TRABAJO
     * @param float $workHeight Alto del TRABAJO
     * @param int $copiesPerSheet TRABAJOS por HOJA
     * @param float $usableWidth Ancho útil de la HOJA
     * @param float $usableHeight Alto útil de la HOJA
     *
     * @return float Porcentaje de aprovechamiento (0-100)
     */
    public function calculateEfficiency(
        float $workWidth,
        float $workHeight,
        int $copiesPerSheet,
        float $usableWidth,
        float $usableHeight
    ): float {
        if ($usableWidth <= 0 || $usableHeight <= 0) {
            return 0;
        }

        // Área útil de la HOJA
        $usableArea = $usableWidth * $usableHeight;

        // Área ocupada por los TRABAJOS
        $workArea = $workWidth * $workHeight;
        $occupiedArea = $workArea * $copiesPerSheet;

        // Porcentaje de aprovechamiento
        $efficiency = ($occupiedArea / $usableArea) * 100;

        return round($efficiency, 2);
    }
}

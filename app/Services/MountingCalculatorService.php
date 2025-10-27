<?php

namespace App\Services;

/**
 * Servicio para cálculo de montaje (cuántas copias caben en un pliego)
 *
 * Calcula cómo posicionar trabajos dentro del área de impresión de una máquina
 * considerando márgenes/sangría obligatorios.
 */
class MountingCalculatorService
{
    /**
     * Calcula cuántas copias de un trabajo caben en un pliego de máquina
     *
     * @param float $workWidth Ancho del trabajo en cm
     * @param float $workHeight Alto del trabajo en cm
     * @param float $machineWidth Ancho máximo del pliego de máquina en cm
     * @param float $machineHeight Alto máximo del pliego de máquina en cm
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
     * Calcula cuántas copias caben en una orientación específica
     *
     * @param float $workW Ancho del trabajo (puede estar rotado)
     * @param float $workH Alto del trabajo (puede estar rotado)
     * @param float $usableW Ancho útil del pliego
     * @param float $usableH Alto útil del pliego
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
        // Calcular cuántas copias caben horizontalmente
        $copiesHorizontal = (int) floor($usableW / $workW);

        // Calcular cuántas copias caben verticalmente
        $copiesVertical = (int) floor($usableH / $workH);

        // Total de copias por pliego
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
     * Calcula pliegos necesarios basándose en cantidad de copias requeridas
     *
     * @param int $requiredCopies Cantidad de copias que el cliente necesita
     * @param int $copiesPerSheet Copias que caben por pliego (del método calculateMounting)
     *
     * @return array [
     *   'sheets_needed' => int,
     *   'total_copies_produced' => int,
     *   'waste_copies' => int
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

        // Pliegos necesarios (redondear hacia arriba)
        $sheetsNeeded = (int) ceil($requiredCopies / $copiesPerSheet);

        // Total de copias producidas
        $totalCopiesProduced = $sheetsNeeded * $copiesPerSheet;

        // Copias de desperdicio
        $wasteCopies = $totalCopiesProduced - $requiredCopies;

        return [
            'sheets_needed' => $sheetsNeeded,
            'total_copies_produced' => $totalCopiesProduced,
            'waste_copies' => $wasteCopies,
        ];
    }

    /**
     * Calcula el aprovechamiento del pliego (porcentaje de área utilizada)
     *
     * @param float $workWidth Ancho del trabajo
     * @param float $workHeight Alto del trabajo
     * @param int $copiesPerSheet Copias por pliego
     * @param float $usableWidth Ancho útil del pliego
     * @param float $usableHeight Alto útil del pliego
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

        // Área útil del pliego
        $usableArea = $usableWidth * $usableHeight;

        // Área ocupada por los trabajos
        $workArea = $workWidth * $workHeight;
        $occupiedArea = $workArea * $copiesPerSheet;

        // Porcentaje de aprovechamiento
        $efficiency = ($occupiedArea / $usableArea) * 100;

        return round($efficiency, 2);
    }
}

<?php

namespace App\Services;

/**
 * Servicio para cálculo de cortes (cuántas HOJAS salen de un PLIEGO)
 *
 * TERMINOLOGÍA:
 * - PLIEGO (100×70cm): Papel del proveedor (tamaño original)
 * - HOJA (ej: 50×35cm): Corte del pliego que va a la máquina de impresión
 * - TRABAJO (ej: 10×15cm): Producto final (volante, tarjeta, etc.)
 *
 * Este servicio calcula cuántas HOJAS (cortes) salen de un PLIEGO.
 * El parámetro "paper" se refiere al PLIEGO (papel del proveedor).
 * El parámetro "cut" se refiere a la HOJA (tamaño del corte/máquina).
 */
class CuttingCalculatorService
{
    /**
     * Calcula cuántas HOJAS salen de un PLIEGO
     *
     * @param float $paperWidth Ancho del PLIEGO (papel proveedor) en cm
     * @param float $paperHeight Alto del PLIEGO (papel proveedor) en cm
     * @param float $cutWidth Ancho de la HOJA (tamaño corte/máquina) en cm
     * @param float $cutHeight Alto de la HOJA (tamaño corte/máquina) en cm
     * @param int $desiredCuts Cantidad de HOJAS deseadas (0 = solo calcular divisor)
     * @param string $orientation Orientación del corte
     *
     * @return array [
     *   'cutsPerSheet' => int (HOJAS por PLIEGO - divisor),
     *   'sheetsNeeded' => int (PLIEGOS necesarios),
     *   ...
     * ]
     */
    public function calculateCuts(
        float $paperWidth,
        float $paperHeight,
        float $cutWidth,
        float $cutHeight,
        int $desiredCuts,
        string $orientation = 'horizontal'
    ): array {
        
        // Validar límites del papel (máximo 125cm como en el JS original)
        if ($paperWidth > 125 || $paperHeight > 125) {
            throw new \InvalidArgumentException('El valor máximo para ancho y/o largo es de 125cm.');
        }

        $largerPaperSide = max($paperWidth, $paperHeight);
        $smallerPaperSide = min($paperWidth, $paperHeight);

        switch ($orientation) {
            case 'horizontal':
                return $this->calculateHorizontal($largerPaperSide, $smallerPaperSide, $cutWidth, $cutHeight, $desiredCuts);
            
            case 'vertical':
                return $this->calculateVertical($largerPaperSide, $smallerPaperSide, $cutWidth, $cutHeight, $desiredCuts);
            
            case 'maximum':
                return $this->calculateMaximum($largerPaperSide, $smallerPaperSide, $cutWidth, $cutHeight, $desiredCuts);
            
            default:
                throw new \InvalidArgumentException('Orientación no válida. Use: horizontal, vertical, maximum');
        }
    }

    private function calculateHorizontal($largerPaperSide, $smallerPaperSide, $cutWidth, $cutHeight, $desiredCuts): array
    {
        // Primer acomodo con orientación horizontal
        $arrangeResult = $this->arrange($largerPaperSide, $smallerPaperSide, 'N', 'H', $cutWidth, $cutHeight);
        $totalCuts = $arrangeResult['totalCuts'];

        // Variable auxiliar inicializada correctamente
        $auxiliarResult = ['totalCuts' => 0, 'verticalCuts' => 0, 'horizontalCuts' => 0];

        // Corregir: verificar sobrante vertical primero (línea 81 del JS)
        if ($arrangeResult['verticalRemainder'] >= $cutHeight) {
            $auxiliarResult = $this->arrange($arrangeResult['verticalRemainder'], $smallerPaperSide, 'H', 'H', $cutWidth, $cutHeight);
            $totalCuts += $auxiliarResult['totalCuts'];
        } else {
            // Segundo caso: verificar sobrante horizontal (línea 86 del JS)
            if ($arrangeResult['horizontalRemainder'] >= $cutWidth) {
                $auxiliarResult = $this->arrange($arrangeResult['horizontalRemainder'], $largerPaperSide, 'H', 'H', $cutWidth, $cutHeight);
                $totalCuts += $auxiliarResult['totalCuts'];
            } else {
                // Cuando no hay espacio, crear resultado vacío (líneas 90-99 del JS)
                $auxiliarResult = [
                    'totalCuts' => 0,
                    'verticalCuts' => 0,
                    'horizontalCuts' => 0,
                    'verticalRemainder' => 0,
                    'horizontalRemainder' => 0,
                    'usedArea' => 0
                ];
            }
        }

        // Lógica de g y f corregida (líneas 103-109 del JS)
        $g = 0; $f = 0;
        if (intval($cutWidth) < intval($cutHeight)) {
            $g = $arrangeResult['totalCuts'];
            $f = $auxiliarResult['totalCuts'];
        } else {
            $g = $auxiliarResult['totalCuts'];
            $f = $arrangeResult['totalCuts'];
        }

        return $this->prepareResults($largerPaperSide, $smallerPaperSide, $cutWidth, $cutHeight, $desiredCuts, $totalCuts, $arrangeResult, $auxiliarResult, 'H', $g, $f);
    }

    private function calculateVertical($largerPaperSide, $smallerPaperSide, $cutWidth, $cutHeight, $desiredCuts): array
    {
        // En orientación vertical, el canvas se dibuja con menor como ancho y mayor como alto
        $arrangeResult = $this->arrange($largerPaperSide, $smallerPaperSide, 'N', 'V', $cutWidth, $cutHeight);
        $totalCuts = $arrangeResult['totalCuts'];

        // Variable auxiliar inicializada correctamente
        $auxiliarResult = ['totalCuts' => 0, 'verticalCuts' => 0, 'horizontalCuts' => 0];

        // Corregir: verificar sobrante vertical primero (igual que JS línea 25)
        if ($arrangeResult['verticalRemainder'] >= $cutHeight) {
            $auxiliarResult = $this->arrange($arrangeResult['verticalRemainder'], $largerPaperSide, 'H', 'H', $cutWidth, $cutHeight);
            $totalCuts += $auxiliarResult['totalCuts'];
        } else {
            // Corregir: usar correctamente las dimensiones para el segundo caso
            if ($arrangeResult['horizontalRemainder'] >= $cutWidth) {
                $auxiliarResult = $this->arrange($arrangeResult['horizontalRemainder'], $smallerPaperSide, 'H', 'H', $cutWidth, $cutHeight);
                $totalCuts += $auxiliarResult['totalCuts'];
            } else {
                // Cuando no hay espacio, crear resultado vacío (líneas 35-43 del JS)
                $auxiliarResult = [
                    'totalCuts' => 0,
                    'verticalCuts' => 0,
                    'horizontalCuts' => 0,
                    'verticalRemainder' => 0,
                    'horizontalRemainder' => 0,
                    'usedArea' => 0
                ];
            }
        }

        // Lógica g y f corregida (líneas 48-54 del JS)
        $g = 0; $f = 0;
        if (intval($cutWidth) < intval($cutHeight)) {
            $g = $arrangeResult['totalCuts'];
            $f = $auxiliarResult['totalCuts'];
        } else {
            $g = $auxiliarResult['totalCuts'];
            $f = $arrangeResult['totalCuts'];
        }

        return $this->prepareResults($smallerPaperSide, $largerPaperSide, $cutWidth, $cutHeight, $desiredCuts, $totalCuts, $arrangeResult, $auxiliarResult, 'V', $g, $f);
    }

    private function calculateMaximum($largerPaperSide, $smallerPaperSide, $cutWidth, $cutHeight, $desiredCuts): array
    {
        $largerCutSide = max($cutWidth, $cutHeight);
        $smallerCutSide = min($cutWidth, $cutHeight);

        // Primer acomodo base (línea 144 del JS)
        $arrangeResult = $this->arrange($largerPaperSide, $smallerPaperSide, 'H', 'M', $largerCutSide, $smallerCutSide);
        $maxTotalCuts = $arrangeResult['totalCuts'];

        // Primera iteración: como en el JS líneas 159-182 (iterando por cortesH)
        $q = [
            'a1b' => $largerPaperSide,
            'a2b' => $largerPaperSide,
            'a1h' => $smallerPaperSide,
            'a2h' => 0,
            'sumaCortes' => $maxTotalCuts,
            'cortesH1' => $arrangeResult['horizontalCuts'],
            'cortesB1' => $arrangeResult['verticalCuts'],
            'cortesT1' => $arrangeResult['totalCuts'],
            'cortesH2' => 0,
            'cortesB2' => 0,
            'cortesT2' => 0
        ];

        // Bucle 1: Iterando por cortesH (líneas 159-182 del JS)
        for ($x = 0; $x <= $arrangeResult['horizontalCuts']; $x++) {
            $o = $largerPaperSide;
            $p = $this->parseFloatFixed(($smallerCutSide * $x) + $arrangeResult['horizontalRemainder'], 2);
            $n = $this->parseFloatFixed($smallerPaperSide - $p, 2);

            if ($n > 0 && $p > 0) {
                $s = $this->arrange($largerPaperSide, $n, 'H', 'N', $largerCutSide, $smallerCutSide);
                $t = $this->arrange($o, $p, 'V', 'N', $largerCutSide, $smallerCutSide);
                $u = $s['totalCuts'] + $t['totalCuts'];

                if ($u > $maxTotalCuts) {
                    $maxTotalCuts = $u;
                    $q = [
                        'a1b' => $largerPaperSide,
                        'a2b' => $o,
                        'a1h' => $n,
                        'a2h' => $p,
                        'sumaCortes' => $maxTotalCuts,
                        'cortesH1' => $s['horizontalCuts'],
                        'cortesB1' => $s['verticalCuts'],
                        'cortesT1' => $s['totalCuts'],
                        'cortesH2' => $t['horizontalCuts'],
                        'cortesB2' => $t['verticalCuts'],
                        'cortesT2' => $t['totalCuts']
                    ];
                }
            }
        }

        // Segunda iteración: como en el JS líneas 193-213 (iterando por cortesB)
        $acumuladorCortesTotales = $arrangeResult['totalCuts'];
        $r = [
            'a1b' => $largerPaperSide,
            'a2b' => 0,
            'a1h' => $smallerPaperSide,
            'a2h' => $smallerPaperSide,
            'sumaCortes' => $acumuladorCortesTotales,
            'cortesH1' => 0,
            'cortesB1' => 0,
            'cortesT1' => 0,
            'cortesH2' => 0,
            'cortesB2' => 0,
            'cortesT2' => 0
        ];

        // Bucle 2: Iterando por cortesB (líneas 193-213 del JS)
        // Corregir el error de la línea 195 del JS donde p está mal declarada
        for ($x = 0; $x <= $arrangeResult['verticalCuts']; $x++) {
            $n = $smallerPaperSide;
            $o = $this->parseFloatFixed(($largerCutSide * $x) + $arrangeResult['verticalRemainder'], 2);
            $m = $this->parseFloatFixed($largerPaperSide - $o, 2);
            $p = $smallerPaperSide; // Corregir: asignar p correctamente

            if ($m > 0 && $o > 0) {
                $s = $this->arrange($m, $n, 'H', 'N', $largerCutSide, $smallerCutSide);
                $t = $this->arrange($o, $p, 'V', 'N', $largerCutSide, $smallerCutSide);
                $u = $s['totalCuts'] + $t['totalCuts'];

                if ($u > $acumuladorCortesTotales) {
                    $acumuladorCortesTotales = $u;
                    $r = [
                        'a1b' => $m,
                        'a2b' => $o,
                        'a1h' => $n,
                        'a2h' => $p,
                        'sumaCortes' => $acumuladorCortesTotales,
                        'cortesH1' => $s['horizontalCuts'],
                        'cortesB1' => $s['verticalCuts'],
                        'cortesT1' => $s['totalCuts'],
                        'cortesH2' => $t['horizontalCuts'],
                        'cortesB2' => $t['verticalCuts'],
                        'cortesT2' => $t['totalCuts']
                    ];
                }
            }
        }

        // Seleccionar el mejor resultado entre q y r (líneas 214-224 del JS)
        $bestResult = $r['sumaCortes'] > $q['sumaCortes'] ? $r : $q;
        $finalMaxCuts = max($r['sumaCortes'], $q['sumaCortes']);

        return $this->prepareResults($largerPaperSide, $smallerPaperSide, $largerCutSide, $smallerCutSide, $desiredCuts, $finalMaxCuts, $bestResult, [], 'M');
    }

    private function parseFloatFixed(float $value, int $precision = 2): float
    {
        return round($value, $precision);
    }

    private function arrange($largerPaperSide, $smallerPaperSide, $cutOrientation, $paperOrientation, $cutWidth, $cutHeight): array
    {
        // Determinar dimensiones del papel según orientación
        if ($paperOrientation === 'V') {
            $paperLarger = min($largerPaperSide, $smallerPaperSide);
            $paperSmaller = max($largerPaperSide, $smallerPaperSide);
        } elseif ($paperOrientation === 'H') {
            $paperLarger = max($largerPaperSide, $smallerPaperSide);
            $paperSmaller = min($largerPaperSide, $smallerPaperSide);
        } else {
            $paperLarger = $largerPaperSide;
            $paperSmaller = $smallerPaperSide;
        }

        // Determinar dimensiones del corte según orientación
        if ($cutOrientation === 'H') {
            $cutLarger = max($cutWidth, $cutHeight);
            $cutSmaller = min($cutWidth, $cutHeight);
        } elseif ($cutOrientation === 'V') {
            $cutLarger = min($cutWidth, $cutHeight);
            $cutSmaller = max($cutWidth, $cutHeight);
        } else {
            $cutLarger = $cutWidth;
            $cutSmaller = $cutHeight;
        }

        // Calcular cortes usando floor en lugar de intval para mayor precisión
        $verticalCuts = floor($paperLarger / $cutLarger);
        $horizontalCuts = floor($paperSmaller / $cutSmaller);
        $totalCuts = $verticalCuts * $horizontalCuts;
        
        $verticalRemainder = round($paperLarger - ($verticalCuts * $cutLarger), 2);
        $horizontalRemainder = round($paperSmaller - ($horizontalCuts * $cutSmaller), 2);
        $usedArea = round(($cutLarger * $cutSmaller) * $totalCuts, 2);

        return [
            'totalCuts' => $totalCuts,
            'verticalCuts' => $verticalCuts,
            'horizontalCuts' => $horizontalCuts,
            'verticalRemainder' => $verticalRemainder,
            'horizontalRemainder' => $horizontalRemainder,
            'usedArea' => $usedArea
        ];
    }

    private function prepareResults($paperLarger, $paperSmaller, $cutWidth, $cutHeight, $desiredCuts, $totalCuts, $arrangeResult, $auxiliarResult, $orientation, $g = null, $f = null): array
    {
        // Calcular área
        $paperArea = $paperLarger * $paperSmaller;
        $cutArea = $cutWidth * $cutHeight;
        $totalCutArea = $totalCuts * $cutArea;
        $usedAreaPercentage = round(($totalCutArea * 100) / $paperArea, 2);
        $wastedAreaPercentage = round(100 - $usedAreaPercentage, 2);

        // Calcular pliegos necesarios
        $sheetsNeeded = ($desiredCuts > 0 && $totalCuts > 0) ? ceil($desiredCuts / $totalCuts) : 0;
        $totalCutsProduced = $totalCuts * $sheetsNeeded;

        // Determinar cortes utilizables según orientación
        $usableCuts = $totalCuts;
        if (isset($auxiliarResult['totalCuts']) && $auxiliarResult['totalCuts'] > 0) {
            if ($cutWidth < $cutHeight) {
                $primary = $arrangeResult['totalCuts'];
                $secondary = $auxiliarResult['totalCuts'];
            } else {
                $primary = $auxiliarResult['totalCuts'];
                $secondary = $arrangeResult['totalCuts'];
            }
            $usableCuts = $primary + $secondary;
        }

        return [
            'cutsPerSheet' => $totalCuts,
            'usableCuts' => $usableCuts,
            'sheetsNeeded' => $sheetsNeeded,
            'totalCutsProduced' => $totalCutsProduced,
            'verticalCuts' => $arrangeResult['verticalCuts'] ?? ($arrangeResult['cortesB1'] ?? 0),
            'horizontalCuts' => $arrangeResult['horizontalCuts'] ?? ($arrangeResult['cortesH1'] ?? 0),
            'usedAreaPercentage' => $usedAreaPercentage,
            'wastedAreaPercentage' => $wastedAreaPercentage,
            'orientation' => $orientation,
            'paperArea' => $paperArea,
            'cutArea' => $cutArea,
            'totalCutArea' => $totalCutArea,
            'arrangeResult' => $arrangeResult,
            'auxiliarResult' => $auxiliarResult ?? []
        ];
    }
}
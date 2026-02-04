<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Services\CuttingCalculatorService;

class CalculadoraCorteWidget extends Widget
{
    protected string $view = 'filament.widgets.calculadora-corte-widget';

    protected int | string | array $columnSpan = 'full';

    public $anchoPapel = 70;
    public $largoPapel = 100;
    public $anchoCorte = 22;
    public $largoCorte = 28;
    public $cantidadDeseada = 1000;
    public $orientacion = 'optimo';

    // Resultados del cálculo
    public $resultado = null;
    public $calculado = false;

    public function mount()
    {
        $this->calcular(); // Calcular automáticamente al cargar
    }

    public function updated($property)
    {
        // Recalcular automáticamente cuando cambien los valores
        if (in_array($property, ['anchoPapel', 'largoPapel', 'anchoCorte', 'largoCorte', 'cantidadDeseada', 'orientacion'])) {
            $this->calcular();
        }
    }

    public function calcular()
    {
        // Validaciones básicas
        if ($this->anchoPapel <= 0 || $this->largoPapel <= 0 ||
            $this->anchoCorte <= 0 || $this->largoCorte <= 0 ||
            $this->cantidadDeseada <= 0) {
            $this->resultado = ['error' => 'Todos los valores deben ser mayores a cero.'];
            $this->calculado = false;
            return;
        }

        // Validación de valores numéricos
        if (!is_numeric($this->anchoPapel) || !is_numeric($this->largoPapel) ||
            !is_numeric($this->anchoCorte) || !is_numeric($this->largoCorte) ||
            !is_numeric($this->cantidadDeseada)) {
            $this->resultado = ['error' => 'Solo se permiten valores numéricos.'];
            $this->calculado = false;
            return;
        }

        try {
            $calculator = new CuttingCalculatorService();

            // Calcular las tres orientaciones usando el servicio corregido
            $horizontal = $calculator->calculateCuts(
                floatval($this->anchoPapel),
                floatval($this->largoPapel),
                floatval($this->anchoCorte),
                floatval($this->largoCorte),
                intval($this->cantidadDeseada),
                'horizontal'
            );

            $vertical = $calculator->calculateCuts(
                floatval($this->anchoPapel),
                floatval($this->largoPapel),
                floatval($this->anchoCorte),
                floatval($this->largoCorte),
                intval($this->cantidadDeseada),
                'vertical'
            );

            $maximum = $calculator->calculateCuts(
                floatval($this->anchoPapel),
                floatval($this->largoPapel),
                floatval($this->anchoCorte),
                floatval($this->largoCorte),
                intval($this->cantidadDeseada),
                'maximum'
            );

            // Convertir resultados al formato esperado por la vista
            $horizontalFormatted = $this->formatResult($horizontal, 'horizontal');
            $verticalFormatted = $this->formatResult($vertical, 'vertical');
            $maximumFormatted = $this->formatResult($maximum, 'maximum');

            switch ($this->orientacion) {
                case 'vertical':
                    $this->resultado = $verticalFormatted;
                    break;
                case 'horizontal':
                    $this->resultado = $horizontalFormatted;
                    break;
                case 'optimo':
                    // Seleccionar el mejor resultado (maximum cuts)
                    $best = $maximum['cutsPerSheet'] >= max($horizontal['cutsPerSheet'], $vertical['cutsPerSheet'])
                        ? $maximumFormatted
                        : ($horizontal['cutsPerSheet'] >= $vertical['cutsPerSheet'] ? $horizontalFormatted : $verticalFormatted);
                    $best['orientacion'] = 'óptimo (' . $best['orientacion'] . ')';
                    $this->resultado = $best;
                    break;
            }

            $this->calculado = true;
            $this->dispatch('calculado');

        } catch (\Exception $e) {
            $this->resultado = ['error' => 'Error en el cálculo: ' . $e->getMessage()];
            $this->calculado = false;
        }
    }

    private function formatResult($result, $orientation)
    {
        $piezasObtenidas = $result['sheetsNeeded'] * $result['cutsPerSheet'];
        $piezasSobrantes = $piezasObtenidas - $this->cantidadDeseada;

        return [
            'orientacion' => $orientation,
            'piezasPorAncho' => $result['horizontalCuts'],
            'piezasPorLargo' => $result['verticalCuts'],
            'cortesHorizontales' => $result['horizontalCuts'],  // Para SVG
            'cortesVerticales' => $result['verticalCuts'],      // Para SVG
            'piezasPorHoja' => $result['cutsPerSheet'],
            'hojasNecesarias' => $result['sheetsNeeded'],
            'piezasObtenidas' => $piezasObtenidas,
            'piezasSobrantes' => $piezasSobrantes,
            'eficiencia' => round($result['usedAreaPercentage'], 2),
            'desperdicioArea' => round(($this->anchoPapel * $this->largoPapel) - ($result['cutsPerSheet'] * $this->anchoCorte * $this->largoCorte), 2),
            'areaUtil' => round($result['cutsPerSheet'] * $this->anchoCorte * $this->largoCorte, 2),
            'areaPapel' => $this->anchoPapel * $this->largoPapel,
            'arrangeResult' => $result['arrangeResult'] ?? null,
            'auxiliarResult' => $result['auxiliarResult'] ?? null,
        ];
    }

    public function setOrientacion($orientacion)
    {
        // Validar orientación válida
        if (!in_array($orientacion, ['vertical', 'horizontal', 'optimo'])) {
            return;
        }

        $this->orientacion = $orientacion;
        $this->calcular();

        // Emitir evento para actualizar el canvas
        $this->dispatch('orientation-changed');
    }

    public function resetCalculator()
    {
        $this->anchoPapel = 70;
        $this->largoPapel = 100;
        $this->anchoCorte = 22;
        $this->largoCorte = 28;
        $this->cantidadDeseada = 1000;
        $this->orientacion = 'optimo';
        $this->resultado = null;
        $this->calculado = false;
        $this->calcular();
    }

    /**
     * Genera visualización SVG del corte con arreglos mixtos
     */
    public function generateCuttingSVG(): string
    {
        if (!$this->calculado || !$this->resultado || isset($this->resultado['error'])) {
            return '';
        }

        $paperWidth = floatval($this->anchoPapel);
        $paperHeight = floatval($this->largoPapel);
        $cutWidth = floatval($this->anchoCorte);
        $cutHeight = floatval($this->largoCorte);

        // Verificar si hay información detallada del arreglo
        $arrangeResult = $this->resultado['arrangeResult'] ?? null;
        $auxiliarResult = $this->resultado['auxiliarResult'] ?? null;

        // Si no hay arrangeResult O no tiene la estructura de arreglo mixto (cortesH1/cortesB1), usar método simple
        if (!$arrangeResult || !isset($arrangeResult['cortesH1']) || !isset($arrangeResult['cortesB1'])) {
            return $this->generateSimpleSVG($paperWidth, $paperHeight, $cutWidth, $cutHeight);
        }

        // Escala para que el SVG sea responsive (max 500px de ancho)
        $maxSvgWidth = 500;
        $scale = $maxSvgWidth / max($paperWidth, $paperHeight);

        // Dimensiones del SVG
        $svgWidth = $paperWidth * $scale;
        $svgHeight = $paperHeight * $scale;

        $svg = '<svg width="' . $svgWidth . '" height="' . $svgHeight . '" viewBox="0 0 ' . $svgWidth . ' ' . $svgHeight . '" xmlns="http://www.w3.org/2000/svg" class="border-2 border-gray-400 rounded shadow-sm">';

        // Fondo del papel (azul claro)
        $svg .= '<rect x="0" y="0" width="' . $svgWidth . '" height="' . $svgHeight . '" fill="#dbeafe" stroke="#3b82f6" stroke-width="2"/>';

        $pieceNumber = 1;

        // ARREGLO PRIMARIO
        if (isset($arrangeResult['cortesH1']) && isset($arrangeResult['cortesB1'])) {
            $cortesH1 = intval($arrangeResult['cortesH1']); // Horizontal cuts
            $cortesB1 = intval($arrangeResult['cortesB1']); // Vertical cuts (B = Base)

            // Calcular orientación del arreglo primario basado en las dimensiones
            // Si el área total es más ancha que alta, las piezas están en orientación horizontal
            $totalWidthArrange1 = floatval($arrangeResult['a1h'] ?? ($cutWidth * $cortesH1));
            $totalHeightArrange1 = floatval($arrangeResult['a1b'] ?? ($cutHeight * $cortesB1));

            // Calcular tamaño real de cada pieza dividiendo área por número de cortes
            $pieceWidthCalc = $totalWidthArrange1 / $cortesH1;
            $pieceHeightCalc = $totalHeightArrange1 / $cortesB1;

            // Determinar orientación comparando dimensiones calculadas con dimensiones originales
            // Si el ancho calculado se parece más al alto del corte, está rotado
            $diffNormal = abs($pieceWidthCalc - $cutWidth) + abs($pieceHeightCalc - $cutHeight);
            $diffRotated = abs($pieceWidthCalc - $cutHeight) + abs($pieceHeightCalc - $cutWidth);

            if ($diffRotated < $diffNormal) {
                // Piezas rotadas (vertical)
                $pieceWidth1 = $cutHeight;
                $pieceHeight1 = $cutWidth;
            } else {
                // Piezas normales (horizontal)
                $pieceWidth1 = $cutWidth;
                $pieceHeight1 = $cutHeight;
            }

            // Dibujar arreglo primario
            for ($row = 0; $row < $cortesB1; $row++) {
                for ($col = 0; $col < $cortesH1; $col++) {
                    $x = ($col * $pieceWidth1) * $scale;
                    $y = ($row * $pieceHeight1) * $scale;
                    $w = $pieceWidth1 * $scale;
                    $h = $pieceHeight1 * $scale;

                    // Rectángulo del corte (verde)
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '"
                        fill="#86efac"
                        stroke="#16a34a"
                        stroke-width="1.5"
                        rx="2"
                        opacity="0.85"/>';

                    // Número de pieza
                    if ($w > 15 && $h > 15) {
                        $fontSize = 10;
                        $svg .= '<text x="' . ($x + $w / 2) . '" y="' . ($y + $h / 2) . '"
                            font-size="' . $fontSize . '"
                            fill="#166534"
                            font-weight="bold"
                            text-anchor="middle"
                            dominant-baseline="middle">' . $pieceNumber . '</text>';
                    }
                    $pieceNumber++;
                }
            }
        }

        // ARREGLO AUXILIAR (si existe)
        if (isset($arrangeResult['cortesH2']) && isset($arrangeResult['cortesB2']) &&
            intval($arrangeResult['cortesH2']) > 0 && intval($arrangeResult['cortesB2']) > 0) {

            $cortesH2 = intval($arrangeResult['cortesH2']);
            $cortesB2 = intval($arrangeResult['cortesB2']);

            // Calcular orientación del arreglo auxiliar
            $totalWidthArrange2 = floatval($arrangeResult['a2h'] ?? ($cutWidth * $cortesH2));
            $totalHeightArrange2 = floatval($arrangeResult['a2b'] ?? ($cutHeight * $cortesB2));

            // Calcular tamaño real de cada pieza auxiliar
            $pieceWidthCalc2 = $totalWidthArrange2 / $cortesH2;
            $pieceHeightCalc2 = $totalHeightArrange2 / $cortesB2;

            // Determinar orientación comparando dimensiones calculadas
            $diffNormal2 = abs($pieceWidthCalc2 - $cutWidth) + abs($pieceHeightCalc2 - $cutHeight);
            $diffRotated2 = abs($pieceWidthCalc2 - $cutHeight) + abs($pieceHeightCalc2 - $cutWidth);

            if ($diffRotated2 < $diffNormal2) {
                // Piezas rotadas (vertical)
                $pieceWidth2 = $cutHeight;
                $pieceHeight2 = $cutWidth;
            } else {
                // Piezas normales (horizontal)
                $pieceWidth2 = $cutWidth;
                $pieceHeight2 = $cutHeight;
            }

            // Determinar posición del arreglo auxiliar
            // En maximum mode, usar las dimensiones a1b y a2b para determinar posición
            $primaryHeight = floatval($arrangeResult['a1b']);
            $auxHeight = floatval($arrangeResult['a2b']);

            // Si hay espacio vertical suficiente después del primario, va abajo
            // Si no, va a la derecha
            $spaceBelow = $paperHeight - $primaryHeight;
            $spaceRight = $paperWidth - floatval($arrangeResult['a1h']);

            if ($spaceBelow >= $auxHeight && $spaceBelow >= $spaceRight) {
                // Auxiliar va en la parte de abajo
                $offsetX = 0;
                $offsetY = $primaryHeight * $scale;
            } else {
                // Auxiliar va a la derecha
                $offsetX = floatval($arrangeResult['a1h']) * $scale;
                $offsetY = 0;
            }

            // Dibujar arreglo auxiliar con color diferente (naranja)
            for ($row = 0; $row < $cortesB2; $row++) {
                for ($col = 0; $col < $cortesH2; $col++) {
                    $x = $offsetX + ($col * $pieceWidth2) * $scale;
                    $y = $offsetY + ($row * $pieceHeight2) * $scale;
                    $w = $pieceWidth2 * $scale;
                    $h = $pieceHeight2 * $scale;

                    // Rectángulo auxiliar (naranja)
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '"
                        fill="#fdba74"
                        stroke="#ea580c"
                        stroke-width="1.5"
                        rx="2"
                        opacity="0.85"/>';

                    // Número de pieza
                    if ($w > 15 && $h > 15) {
                        $fontSize = 10;
                        $svg .= '<text x="' . ($x + $w / 2) . '" y="' . ($y + $h / 2) . '"
                            font-size="' . $fontSize . '"
                            fill="#7c2d12"
                            font-weight="bold"
                            text-anchor="middle"
                            dominant-baseline="middle">' . $pieceNumber . '</text>';
                    }
                    $pieceNumber++;
                }
            }
        }

        // Dimensiones del papel (texto superior)
        $svg .= '<text x="' . ($svgWidth / 2) . '" y="15" font-size="12" fill="#1e40af" font-weight="bold" text-anchor="middle">' . $paperWidth . 'cm</text>';
        $svg .= '<text x="15" y="' . ($svgHeight / 2) . '" font-size="12" fill="#1e40af" font-weight="bold" text-anchor="middle" transform="rotate(-90 15 ' . ($svgHeight / 2) . ')">' . $paperHeight . 'cm</text>';

        // Información de eficiencia (parte inferior)
        $efficiency = $this->resultado['eficiencia'] ?? 0;
        $totalPieces = $this->resultado['piezasPorHoja'] ?? 0;
        $svg .= '<text x="' . ($svgWidth / 2) . '" y="' . ($svgHeight - 10) . '" font-size="11" fill="#374151" text-anchor="middle">' . $totalPieces . ' piezas | ' . $efficiency . '% eficiencia</text>';

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Método mejorado para dibujar arreglos simples y mixtos
     */
    private function generateSimpleSVG($paperWidth, $paperHeight, $cutWidth, $cutHeight): string
    {
        // IMPORTANTE: El PLIEGO siempre se dibuja VERTICAL (ancho menor que alto)
        $paperWidthForSVG = min($paperWidth, $paperHeight);
        $paperHeightForSVG = max($paperWidth, $paperHeight);

        // Obtener información del servicio
        $arrangeResult = $this->resultado['arrangeResult'] ?? null;
        $auxiliarResult = $this->resultado['auxiliarResult'] ?? null;
        $orientacion = $this->resultado['orientacion'];
        $isHorizontal = strpos($orientacion, 'horizontal') !== false;

        // Determinar orientación de las hojas según el modo
        if ($isHorizontal) {
            // Horizontal: hojas rotadas
            $pieceWidth = max($cutWidth, $cutHeight);
            $pieceHeight = min($cutWidth, $cutHeight);
        } else {
            // Vertical: hojas en orientación original
            $pieceWidth = min($cutWidth, $cutHeight);
            $pieceHeight = max($cutWidth, $cutHeight);
        }

        // Obtener información del arreglo principal
        $horizontalCuts = $arrangeResult['horizontalCuts'] ?? 0;
        $verticalCuts = $arrangeResult['verticalCuts'] ?? 0;

        $maxSvgWidth = 500;
        $scale = $maxSvgWidth / max($paperWidthForSVG, $paperHeightForSVG);
        $svgWidth = $paperWidthForSVG * $scale;
        $svgHeight = $paperHeightForSVG * $scale;
        $pieceWidthScaled = $pieceWidth * $scale;
        $pieceHeightScaled = $pieceHeight * $scale;

        $svg = '<svg width="' . $svgWidth . '" height="' . $svgHeight . '" viewBox="0 0 ' . $svgWidth . ' ' . $svgHeight . '" xmlns="http://www.w3.org/2000/svg" class="border-2 border-gray-400 rounded shadow-sm">';
        $svg .= '<rect x="0" y="0" width="' . $svgWidth . '" height="' . $svgHeight . '" fill="#dbeafe" stroke="#3b82f6" stroke-width="2"/>';

        $pieceNumber = 1;

        // ARREGLO PRINCIPAL (verde)
        for ($row = 0; $row < $verticalCuts; $row++) {
            for ($col = 0; $col < $horizontalCuts; $col++) {
                $x = ($col * $pieceWidthScaled);
                $y = ($row * $pieceHeightScaled);

                // VALIDACIÓN: Solo dibujar si la pieza cabe completamente dentro del papel
                $pieceEndX = $x + $pieceWidthScaled;
                $pieceEndY = $y + $pieceHeightScaled;

                if ($pieceEndX <= $svgWidth && $pieceEndY <= $svgHeight) {
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $pieceWidthScaled . '" height="' . $pieceHeightScaled . '"
                        fill="#86efac" stroke="#16a34a" stroke-width="1.5" rx="2" opacity="0.85"/>';

                    if ($pieceWidthScaled > 15 && $pieceHeightScaled > 15) {
                        $svg .= '<text x="' . ($x + $pieceWidthScaled / 2) . '" y="' . ($y + $pieceHeightScaled / 2) . '"
                            font-size="10" fill="#166534" font-weight="bold" text-anchor="middle" dominant-baseline="middle">' . $pieceNumber . '</text>';
                    }
                    $pieceNumber++;
                }
            }
        }

        // ARREGLO AUXILIAR (naranja) - si existe
        if ($auxiliarResult && isset($auxiliarResult['horizontalCuts']) && isset($auxiliarResult['verticalCuts'])) {
            $auxHorizontalCuts = $auxiliarResult['horizontalCuts'];
            $auxVerticalCuts = $auxiliarResult['verticalCuts'];

            if ($auxHorizontalCuts > 0 && $auxVerticalCuts > 0) {
                // Calcular posición del arreglo auxiliar según orientación
                // El auxiliar se coloca en el espacio sobrante del principal
                $verticalRemainder = $arrangeResult['verticalRemainder'] ?? 0;
                $horizontalRemainder = $arrangeResult['horizontalRemainder'] ?? 0;

                // Determinar si el auxiliar va vertical u horizontal según los restos
                if ($verticalRemainder >= $cutHeight && $verticalRemainder >= $horizontalRemainder) {
                    // Auxiliar en el espacio sobrante vertical (abajo del principal)
                    $auxStartX = 0;
                    $auxStartY = $verticalCuts * $pieceHeightScaled;
                    $auxPieceWidth = $pieceWidth * $scale;
                    $auxPieceHeight = $pieceHeight * $scale;
                } else {
                    // Auxiliar en el espacio sobrante horizontal (derecha del principal)
                    $auxStartX = $horizontalCuts * $pieceWidthScaled;
                    $auxStartY = 0;
                    $auxPieceWidth = $pieceWidth * $scale;
                    $auxPieceHeight = $pieceHeight * $scale;
                }

                // Dibujar piezas auxiliares solo si caben dentro del papel
                for ($row = 0; $row < $auxVerticalCuts; $row++) {
                    for ($col = 0; $col < $auxHorizontalCuts; $col++) {
                        $x = $auxStartX + ($col * $auxPieceWidth);
                        $y = $auxStartY + ($row * $auxPieceHeight);

                        // VALIDACIÓN: Solo dibujar si la pieza cabe completamente dentro del papel
                        $pieceEndX = $x + $auxPieceWidth;
                        $pieceEndY = $y + $auxPieceHeight;

                        if ($pieceEndX <= $svgWidth && $pieceEndY <= $svgHeight) {
                            $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $auxPieceWidth . '" height="' . $auxPieceHeight . '"
                                fill="#fed7aa" stroke="#ea580c" stroke-width="1.5" rx="2" opacity="0.85"/>';

                            if ($auxPieceWidth > 15 && $auxPieceHeight > 15) {
                                $svg .= '<text x="' . ($x + $auxPieceWidth / 2) . '" y="' . ($y + $auxPieceHeight / 2) . '"
                                    font-size="10" fill="#9a3412" font-weight="bold" text-anchor="middle" dominant-baseline="middle">' . $pieceNumber . '</text>';
                            }
                            $pieceNumber++;
                        }
                    }
                }
            }
        }

        $svg .= '<text x="' . ($svgWidth / 2) . '" y="15" font-size="12" fill="#1e40af" font-weight="bold" text-anchor="middle">' . $paperWidthForSVG . 'cm</text>';
        $svg .= '<text x="15" y="' . ($svgHeight / 2) . '" font-size="12" fill="#1e40af" font-weight="bold" text-anchor="middle" transform="rotate(-90 15 ' . ($svgHeight / 2) . ')">' . $paperHeightForSVG . 'cm</text>';

        $efficiency = $this->resultado['eficiencia'] ?? 0;
        $totalPieces = $this->resultado['piezasPorHoja'] ?? ($horizontalCuts * $verticalCuts);
        $svg .= '<text x="' . ($svgWidth / 2) . '" y="' . ($svgHeight - 10) . '" font-size="11" fill="#374151" text-anchor="middle">' . $totalPieces . ' piezas | ' . $efficiency . '% eficiencia</text>';

        $svg .= '</svg>';
        return $svg;
    }
}
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
            'piezasPorHoja' => $result['cutsPerSheet'],
            'hojasNecesarias' => $result['sheetsNeeded'],
            'piezasObtenidas' => $piezasObtenidas,
            'piezasSobrantes' => $piezasSobrantes,
            'eficiencia' => round($result['usedAreaPercentage'], 2),
            'desperdicioArea' => round(($this->anchoPapel * $this->largoPapel) - ($result['cutsPerSheet'] * $this->anchoCorte * $this->largoCorte), 2),
            'areaUtil' => round($result['cutsPerSheet'] * $this->anchoCorte * $this->largoCorte, 2),
            'areaPapel' => $this->anchoPapel * $this->largoPapel
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
        $this->anchoCorte = "";
        $this->largoCorte = "";
        $this->cantidadDeseada = 1000;
        $this->orientacion = 'optimo';
        $this->resultado = null;
        $this->calculado = false;
    }
}
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class CalculadoraCorteWidget extends Widget
{
    protected string $view = 'filament.widgets.calculadora-corte-widget';

    protected int | string | array $columnSpan = 'full';

    public $anchoPapel = 70;
    public $largoPapel = 100;
    public $anchoCorte = 10;
    public $largoCorte = 15;
    public $cantidadDeseada = 1000;
    public $orientacion = 'vertical';

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

        // Verificar que el corte quepa en el papel
        if ($this->anchoCorte > $this->anchoPapel || $this->largoCorte > $this->largoPapel) {
            if ($this->anchoCorte > $this->largoPapel || $this->largoCorte > $this->anchoPapel) {
                $this->resultado = ['error' => 'El corte es demasiado grande para el papel.'];
                $this->calculado = false;
                return;
            }
        }

        $vertical = $this->calcularVertical();
        $horizontal = $this->calcularHorizontal();

        switch ($this->orientacion) {
            case 'vertical':
                $this->resultado = $vertical;
                break;
            case 'horizontal':
                $this->resultado = $horizontal;
                break;
            case 'optimo':
                $this->resultado = $this->seleccionarOptimo($vertical, $horizontal);
                break;
        }

        $this->calculado = true;
        $this->dispatch('calculado');
    }

    private function calcularVertical()
    {
        $piezasPorAncho = floor($this->anchoPapel / $this->anchoCorte);
        $piezasPorLargo = floor($this->largoPapel / $this->largoCorte);
        $piezasPorHoja = $piezasPorAncho * $piezasPorLargo;

        if ($piezasPorHoja == 0) {
            return ['error' => 'No cabe ninguna pieza en orientación vertical'];
        }

        $hojasNecesarias = ceil($this->cantidadDeseada / $piezasPorHoja);
        $piezasObtenidas = $hojasNecesarias * $piezasPorHoja;
        $piezasSobrantes = $piezasObtenidas - $this->cantidadDeseada;

        // Cálculo de desperdicios
        $areaUtil = $piezasPorAncho * $this->anchoCorte * $piezasPorLargo * $this->largoCorte;
        $areaPapel = $this->anchoPapel * $this->largoPapel;
        $desperdicioArea = $areaPapel - $areaUtil;
        $eficiencia = ($areaUtil / $areaPapel) * 100;

        return [
            'orientacion' => 'vertical',
            'piezasPorAncho' => $piezasPorAncho,
            'piezasPorLargo' => $piezasPorLargo,
            'piezasPorHoja' => $piezasPorHoja,
            'hojasNecesarias' => $hojasNecesarias,
            'piezasObtenidas' => $piezasObtenidas,
            'piezasSobrantes' => $piezasSobrantes,
            'eficiencia' => round($eficiencia, 2),
            'desperdicioArea' => round($desperdicioArea, 2),
            'areaUtil' => round($areaUtil, 2),
            'areaPapel' => $areaPapel
        ];
    }

    private function calcularHorizontal()
    {
        $piezasPorAncho = floor($this->anchoPapel / $this->largoCorte);
        $piezasPorLargo = floor($this->largoPapel / $this->anchoCorte);
        $piezasPorHoja = $piezasPorAncho * $piezasPorLargo;

        if ($piezasPorHoja == 0) {
            return ['error' => 'No cabe ninguna pieza en orientación horizontal'];
        }

        $hojasNecesarias = ceil($this->cantidadDeseada / $piezasPorHoja);
        $piezasObtenidas = $hojasNecesarias * $piezasPorHoja;
        $piezasSobrantes = $piezasObtenidas - $this->cantidadDeseada;

        // Cálculo de desperdicios
        $areaUtil = $piezasPorAncho * $this->largoCorte * $piezasPorLargo * $this->anchoCorte;
        $areaPapel = $this->anchoPapel * $this->largoPapel;
        $desperdicioArea = $areaPapel - $areaUtil;
        $eficiencia = ($areaUtil / $areaPapel) * 100;

        return [
            'orientacion' => 'horizontal',
            'piezasPorAncho' => $piezasPorAncho,
            'piezasPorLargo' => $piezasPorLargo,
            'piezasPorHoja' => $piezasPorHoja,
            'hojasNecesarias' => $hojasNecesarias,
            'piezasObtenidas' => $piezasObtenidas,
            'piezasSobrantes' => $piezasSobrantes,
            'eficiencia' => round($eficiencia, 2),
            'desperdicioArea' => round($desperdicioArea, 2),
            'areaUtil' => round($areaUtil, 2),
            'areaPapel' => $areaPapel
        ];
    }

    private function seleccionarOptimo($vertical, $horizontal)
    {
        if (isset($vertical['error']) && isset($horizontal['error'])) {
            return ['error' => 'No cabe en ninguna orientación'];
        }

        if (isset($vertical['error'])) return $horizontal;
        if (isset($horizontal['error'])) return $vertical;

        // Seleccionar el que tenga mejor eficiencia
        if ($vertical['eficiencia'] >= $horizontal['eficiencia']) {
            $vertical['orientacion'] = 'óptimo (vertical)';
            return $vertical;
        } else {
            $horizontal['orientacion'] = 'óptimo (horizontal)';
            return $horizontal;
        }
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
        $this->anchoCorte = 10;
        $this->largoCorte = 15;
        $this->cantidadDeseada = 1000;
        $this->orientacion = 'vertical';
        $this->resultado = null;
        $this->calculado = false;
    }
}
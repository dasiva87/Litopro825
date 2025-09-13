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

    public function calcular()
    {
        // Lógica de cálculo aquí
        $this->dispatch('calculado');
    }

    public function setOrientacion($orientacion)
    {
        $this->orientacion = $orientacion;
    }
}
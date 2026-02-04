<?php

namespace App\Filament\Widgets;

use App\Services\CuttingCalculatorService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;

class CalculadoraButtonWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected string $view = 'filament.widgets.calculadora-button-widget';

    protected static ?int $sort = 10;

    protected int | string | array $columnSpan = 'full';

    public function calculadoraAction(): Action
    {
        return Action::make('calculadora')
            ->label('Abrir')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('warning')
            ->size('sm')
            ->slideOver()
            ->modalHeading('Calculadora de Corte')
            ->modalDescription('Calcula cuántas piezas caben en una hoja de papel')
            ->modalWidth('7xl')
            ->modalContent(view('filament.widgets.calculadora-modal-content'))
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->closeModalByClickingAway(false);
    }

    private function calculateResults(array $data): array
    {
        try {
            $calculator = new CuttingCalculatorService();

            $horizontal = $calculator->calculateCuts(
                floatval($data['anchoPapel'] ?? 70),
                floatval($data['largoPapel'] ?? 100),
                floatval($data['anchoCorte'] ?? 22),
                floatval($data['largoCorte'] ?? 28),
                intval($data['cantidadDeseada'] ?? 1000),
                'horizontal'
            );

            $vertical = $calculator->calculateCuts(
                floatval($data['anchoPapel'] ?? 70),
                floatval($data['largoPapel'] ?? 100),
                floatval($data['anchoCorte'] ?? 22),
                floatval($data['largoCorte'] ?? 28),
                intval($data['cantidadDeseada'] ?? 1000),
                'vertical'
            );

            $maximum = $calculator->calculateCuts(
                floatval($data['anchoPapel'] ?? 70),
                floatval($data['largoPapel'] ?? 100),
                floatval($data['anchoCorte'] ?? 22),
                floatval($data['largoCorte'] ?? 28),
                intval($data['cantidadDeseada'] ?? 1000),
                'maximum'
            );

            $orientacion = $data['orientacion'] ?? 'optimo';

            switch ($orientacion) {
                case 'vertical':
                    $result = $vertical;
                    break;
                case 'horizontal':
                    $result = $horizontal;
                    break;
                case 'optimo':
                    $best = $maximum['cutsPerSheet'] >= max($horizontal['cutsPerSheet'], $vertical['cutsPerSheet'])
                        ? $maximum
                        : ($horizontal['cutsPerSheet'] >= $vertical['cutsPerSheet'] ? $horizontal : $vertical);
                    $result = $best;
                    break;
                default:
                    $result = $maximum;
            }

            $piezasObtenidas = $result['sheetsNeeded'] * $result['cutsPerSheet'];
            $piezasSobrantes = $piezasObtenidas - intval($data['cantidadDeseada'] ?? 1000);

            return [
                'success' => true,
                'piezasPorHoja' => $result['cutsPerSheet'],
                'hojasNecesarias' => $result['sheetsNeeded'],
                'piezasObtenidas' => $piezasObtenidas,
                'piezasSobrantes' => $piezasSobrantes,
                'eficiencia' => round($result['usedAreaPercentage'], 2),
                'cortesHorizontales' => $result['horizontalCuts'],
                'cortesVerticales' => $result['verticalCuts'],
                'anchoPapel' => $data['anchoPapel'] ?? 70,
                'largoPapel' => $data['largoPapel'] ?? 100,
                'anchoCorte' => $data['anchoCorte'] ?? 22,
                'largoCorte' => $data['largoCorte'] ?? 28,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error en el cálculo: ' . $e->getMessage(),
            ];
        }
    }
}

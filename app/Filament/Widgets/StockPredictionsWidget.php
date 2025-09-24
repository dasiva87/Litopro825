<?php

namespace App\Filament\Widgets;

use App\Services\StockPredictionService;
use Filament\Widgets\Widget;

class StockPredictionsWidget extends Widget
{
    protected string $view = 'filament.widgets.stock-predictions';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    public function getViewData(): array
    {
        $predictionService = app(StockPredictionService::class);

        $predictions = $predictionService->getReorderAlerts(
            auth()->user()->company_id,
            30
        );

        return [
            'predictions' => $predictions,
        ];
    }
}
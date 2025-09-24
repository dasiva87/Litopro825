<?php

namespace App\Filament\Widgets;

use App\Models\Paper;
use App\Services\CuttingCalculatorService;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class PaperCalculatorWidget extends Widget
{
    protected string $view = 'filament.widgets.paper-calculator';
    
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];
    
    // Calculator state
    public $paperSize = 'carta'; // Default paper size
    public $itemWidth = '';
    public $itemHeight = '';
    public $selectedPaper = null;
    public $calculation = null;
    public $showResults = false;
    
    // Predefined paper sizes
    public $paperSizes = [
        'carta' => ['width' => 21.6, 'height' => 27.9, 'label' => 'Carta (21.6 x 27.9 cm)'],
        'legal' => ['width' => 21.6, 'height' => 35.6, 'label' => 'Legal (21.6 x 35.6 cm)'],
        'a4' => ['width' => 21, 'height' => 29.7, 'label' => 'A4 (21 x 29.7 cm)'],
        'a3' => ['width' => 29.7, 'height' => 42, 'label' => 'A3 (29.7 x 42 cm)'],
        'tabloid' => ['width' => 27.9, 'height' => 43.2, 'label' => 'Tabloide (27.9 x 43.2 cm)'],
        'custom' => ['width' => 0, 'height' => 0, 'label' => 'Personalizado'],
    ];
    
    public $customPaperWidth = '';
    public $customPaperHeight = '';
    
    public function mount(): void
    {
        $this->selectedPaper = $this->paperSizes[$this->paperSize];
    }
    
    public function updatedPaperSize(): void
    {
        $this->selectedPaper = $this->paperSizes[$this->paperSize];
        $this->showResults = false;
        $this->calculation = null;
    }
    
    public function updatedCustomPaperWidth(): void
    {
        if ($this->paperSize === 'custom') {
            $this->selectedPaper['width'] = floatval($this->customPaperWidth);
        }
    }
    
    public function updatedCustomPaperHeight(): void
    {
        if ($this->paperSize === 'custom') {
            $this->selectedPaper['height'] = floatval($this->customPaperHeight);
        }
    }
    
    public function calculate(): void
    {
        $this->validate([
            'itemWidth' => 'required|numeric|min:0.1',
            'itemHeight' => 'required|numeric|min:0.1',
        ], [
            'itemWidth.required' => 'El ancho del item es requerido',
            'itemWidth.numeric' => 'El ancho debe ser un número',
            'itemWidth.min' => 'El ancho debe ser mayor a 0.1 cm',
            'itemHeight.required' => 'El alto del item es requerido',
            'itemHeight.numeric' => 'El alto debe ser un número',
            'itemHeight.min' => 'El alto debe ser mayor a 0.1 cm',
        ]);

        if ($this->paperSize === 'custom') {
            $this->validate([
                'customPaperWidth' => 'required|numeric|min:1',
                'customPaperHeight' => 'required|numeric|min:1',
            ], [
                'customPaperWidth.required' => 'El ancho del papel es requerido',
                'customPaperHeight.required' => 'El alto del papel es requerido',
            ]);

            $paperWidth = floatval($this->customPaperWidth);
            $paperHeight = floatval($this->customPaperHeight);
        } else {
            $paperWidth = $this->selectedPaper['width'];
            $paperHeight = $this->selectedPaper['height'];
        }

        $calculator = new CuttingCalculatorService();

        // Calculate all three orientations using the corrected service
        $horizontalResult = $calculator->calculateCuts(
            $paperWidth,
            $paperHeight,
            floatval($this->itemWidth),
            floatval($this->itemHeight),
            1000, // Default quantity for calculation
            'horizontal'
        );

        $verticalResult = $calculator->calculateCuts(
            $paperWidth,
            $paperHeight,
            floatval($this->itemWidth),
            floatval($this->itemHeight),
            1000,
            'vertical'
        );

        $maximumResult = $calculator->calculateCuts(
            $paperWidth,
            $paperHeight,
            floatval($this->itemWidth),
            floatval($this->itemHeight),
            1000,
            'maximum'
        );

        // Select the best option (maximum cuts)
        $results = [
            'horizontal' => $horizontalResult,
            'vertical' => $verticalResult,
            'maximum' => $maximumResult
        ];

        $bestOrientation = array_reduce(array_keys($results), function($best, $current) use ($results) {
            return $results[$current]['cutsPerSheet'] > $results[$best]['cutsPerSheet'] ? $current : $best;
        }, 'horizontal');

        $this->calculation = [
            'horizontal' => [
                'cuts_h' => $horizontalResult['horizontalCuts'],
                'cuts_v' => $horizontalResult['verticalCuts'],
                'total_cuts' => $horizontalResult['cutsPerSheet'],
                'efficiency' => $horizontalResult['usedAreaPercentage'],
                'waste' => $horizontalResult['wastedAreaPercentage'],
                'orientation' => 'horizontal'
            ],
            'vertical' => [
                'cuts_h' => $verticalResult['horizontalCuts'],
                'cuts_v' => $verticalResult['verticalCuts'],
                'total_cuts' => $verticalResult['cutsPerSheet'],
                'efficiency' => $verticalResult['usedAreaPercentage'],
                'waste' => $verticalResult['wastedAreaPercentage'],
                'orientation' => 'vertical'
            ],
            'maximum' => [
                'cuts_h' => $maximumResult['horizontalCuts'],
                'cuts_v' => $maximumResult['verticalCuts'],
                'total_cuts' => $maximumResult['cutsPerSheet'],
                'efficiency' => $maximumResult['usedAreaPercentage'],
                'waste' => $maximumResult['wastedAreaPercentage'],
                'orientation' => 'maximum'
            ],
            'best' => [
                'cuts_h' => $results[$bestOrientation]['horizontalCuts'],
                'cuts_v' => $results[$bestOrientation]['verticalCuts'],
                'total_cuts' => $results[$bestOrientation]['cutsPerSheet'],
                'efficiency' => $results[$bestOrientation]['usedAreaPercentage'],
                'waste' => $results[$bestOrientation]['wastedAreaPercentage'],
                'orientation' => $bestOrientation,
                'orientation_label' => ucfirst($bestOrientation)
            ],
            'paper_size' => [
                'width' => $paperWidth,
                'height' => $paperHeight,
                'area' => $paperWidth * $paperHeight
            ],
            'item_size' => [
                'width' => floatval($this->itemWidth),
                'height' => floatval($this->itemHeight),
                'area' => floatval($this->itemWidth) * floatval($this->itemHeight)
            ]
        ];

        $this->showResults = true;
    }
    
    public function resetCalculator(): void
    {
        $this->itemWidth = '';
        $this->itemHeight = '';
        $this->customPaperWidth = '';
        $this->customPaperHeight = '';
        $this->showResults = false;
        $this->calculation = null;
        $this->paperSize = 'carta';
        $this->selectedPaper = $this->paperSizes[$this->paperSize];
    }
    
    public function getAvailablePapers(): Collection
    {
        return Paper::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'width', 'height', 'weight']);
    }
    
    public function selectPredefinedPaper($paperId): void
    {
        $paper = Paper::find($paperId);
        
        if ($paper) {
            $this->paperSize = 'custom';
            $this->customPaperWidth = $paper->width;
            $this->customPaperHeight = $paper->height;
            $this->selectedPaper = [
                'width' => $paper->width,
                'height' => $paper->height,
                'label' => "{$paper->name} ({$paper->width} x {$paper->height} cm)"
            ];
        }
    }
    
    public function getViewData(): array
    {
        return [
            'paperSizes' => $this->paperSizes,
            'availablePapers' => $this->getAvailablePapers(),
            'calculation' => $this->calculation,
            'showResults' => $this->showResults,
        ];
    }
}
<?php

namespace App\Filament\Widgets;

use App\Models\Paper;
use App\Services\CuttingCalculatorService;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class PaperCalculatorWidget extends Widget
{
    protected string $view = 'filament.widgets.paper-calculator';
    
    protected static ?int $sort = 12;
    
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
        
        // Calculate both orientations
        $horizontalResult = $calculator->calculateOptimalCutting(
            $paperWidth,
            $paperHeight,
            floatval($this->itemWidth),
            floatval($this->itemHeight)
        );
        
        $verticalResult = $calculator->calculateOptimalCutting(
            $paperWidth,
            $paperHeight,
            floatval($this->itemHeight), // Swap dimensions
            floatval($this->itemWidth)
        );
        
        // Select the best option
        $bestResult = $horizontalResult->totalCuts >= $verticalResult->totalCuts 
            ? $horizontalResult 
            : $verticalResult;
        
        $this->calculation = [
            'horizontal' => [
                'cuts_h' => $horizontalResult->cutsH,
                'cuts_v' => $horizontalResult->cutsV,
                'total_cuts' => $horizontalResult->totalCuts,
                'efficiency' => $horizontalResult->efficiency,
                'waste_area' => $horizontalResult->wasteArea,
                'orientation' => 'horizontal'
            ],
            'vertical' => [
                'cuts_h' => $verticalResult->cutsV, // Note: swapped because we rotated
                'cuts_v' => $verticalResult->cutsH,
                'total_cuts' => $verticalResult->totalCuts,
                'efficiency' => $verticalResult->efficiency,
                'waste_area' => $verticalResult->wasteArea,
                'orientation' => 'vertical'
            ],
            'best' => [
                'cuts_h' => $bestResult === $horizontalResult ? $bestResult->cutsH : $bestResult->cutsV,
                'cuts_v' => $bestResult === $horizontalResult ? $bestResult->cutsV : $bestResult->cutsH,
                'total_cuts' => $bestResult->totalCuts,
                'efficiency' => $bestResult->efficiency,
                'waste_area' => $bestResult->wasteArea,
                'orientation' => $bestResult === $horizontalResult ? 'horizontal' : 'vertical',
                'orientation_label' => $bestResult === $horizontalResult ? 'Horizontal' : 'Vertical'
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
<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\DocumentType;
use App\Models\DocumentItem;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Services\CuttingCalculatorService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    public function mount(): void
    {
        parent::mount();
        
        $this->form->fill([
            'document_type_id' => DocumentType::where('code', 'QUOTE')->first()?->id,
            'date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'valid_until' => now()->addDays(15)->format('Y-m-d'),
            'status' => 'draft',
            'tax_percentage' => 19,
        ]);
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        
        // Crear los items si existen
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $itemData) {
                DocumentItem::create([
                    'document_id' => $this->record->id,
                    'paper_id' => $itemData['paper_id'] ?? null,
                    'printing_machine_id' => $itemData['printing_machine_id'] ?? null,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'] ?? 1,
                    'width' => $itemData['width'] ?? null,
                    'height' => $itemData['height'] ?? null,
                    'pages' => $itemData['pages'] ?? 1,
                    'colors_front' => $itemData['colors_front'] ?? 0,
                    'colors_back' => $itemData['colors_back'] ?? 0,
                    'orientation' => $itemData['orientation'] ?? 'horizontal',
                    'cuts_per_sheet' => $itemData['cuts_per_sheet'] ?? 1,
                    'sheets_needed' => $itemData['sheets_needed'] ?? 1,
                    'paper_cost' => $itemData['paper_cost'] ?? 0,
                    'printing_cost' => $itemData['printing_cost'] ?? 0,
                    'cutting_cost' => $itemData['cutting_cost'] ?? 0,
                    'design_cost' => $itemData['design_cost'] ?? 0,
                    'other_costs' => $itemData['other_costs'] ?? 0,
                    'profit_margin' => $itemData['profit_margin'] ?? 30,
                    'item_type' => $itemData['item_type'] ?? 'simple',
                ]);
            }
        }

        // Recalcular totales del documento
        $this->record->calculateTotals();
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        $data['user_id'] = auth()->id();
        $data['status'] = 'draft';

        // Asegurar que siempre se cree como QUOTE
        if (!isset($data['document_type_id'])) {
            $data['document_type_id'] = DocumentType::where('code', 'QUOTE')->first()?->id;
        }

        return $data;
    }

}
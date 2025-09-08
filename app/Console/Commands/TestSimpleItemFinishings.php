<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SimpleItem;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Finishing;
use App\Models\DocumentItemFinishing;
use App\Services\FinishingCalculatorService;

class TestSimpleItemFinishings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:simple-item-finishings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SimpleItem finishings functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing SimpleItem with finishings...');
        
        // Buscar una cotización existente
        $document = Document::first();
        if (!$document) {
            $this->error('No se encontró una cotización para probar');
            return;
        }
        
        $this->info("Usando documento: {$document->document_number}");
        
        // Buscar un acabado disponible
        $finishing = Finishing::where('active', true)
            ->where('company_id', $document->company_id)
            ->first();
            
        if (!$finishing) {
            $this->error('No se encontró un acabado disponible');
            return;
        }
        
        $this->info("Usando acabado: {$finishing->name} ({$finishing->measurement_unit->value})");
        
        // Buscar papel y máquina disponibles
        $paper = \App\Models\Paper::where('company_id', $document->company_id)->first();
        $machine = \App\Models\PrintingMachine::where('company_id', $document->company_id)->first();
        
        if (!$paper || !$machine) {
            $this->error('No se encontró papel o máquina de impresión disponible');
            return;
        }
        
        // Crear un SimpleItem de prueba con todos los datos necesarios
        $simpleItemData = [
            'description' => 'Test SimpleItem con acabados',
            'quantity' => 100,
            'horizontal_size' => 21.0,
            'vertical_size' => 29.7,
            'paper_id' => $paper->id,
            'printing_machine_id' => $machine->id,
            'ink_front_count' => 1,
            'ink_back_count' => 0,
            'front_back_plate' => false,
            'profit_percentage' => 30,
            'company_id' => $document->company_id,
            'user_id' => $document->user_id,
        ];
        
        $simpleItem = SimpleItem::create($simpleItemData);
        $simpleItem->calculateAll(); // Calcular costos y precio final
        $this->info("SimpleItem creado con ID: {$simpleItem->id}");
        $this->info("Precio base: $" . number_format($simpleItem->final_price, 2));
        
        // Calcular costo del acabado
        $calculator = app(FinishingCalculatorService::class);
        $finishingCost = $calculator->calculateCost($finishing, [
            'quantity' => $simpleItem->quantity,
            'width' => $simpleItem->horizontal_size,
            'height' => $simpleItem->vertical_size,
        ]);
        
        $this->info("Costo del acabado calculado: $" . number_format($finishingCost, 2));
        
        // Crear DocumentItem con acabados
        $totalPriceWithFinishings = $simpleItem->final_price + $finishingCost;
        $unitPriceWithFinishings = $totalPriceWithFinishings / $simpleItem->quantity;
        
        $documentItem = DocumentItem::create([
            'document_id' => $document->id,
            'itemable_type' => 'App\\Models\\SimpleItem',
            'itemable_id' => $simpleItem->id,
            'description' => 'SimpleItem Test: ' . $simpleItem->description,
            'quantity' => $simpleItem->quantity,
            'unit_price' => $unitPriceWithFinishings,
            'total_price' => $totalPriceWithFinishings,
        ]);
        
        $this->info("DocumentItem creado con ID: {$documentItem->id}");
        $this->info("Total con acabados: $" . number_format($totalPriceWithFinishings, 2));
        
        // Crear el acabado asociado
        DocumentItemFinishing::create([
            'document_item_id' => $documentItem->id,
            'finishing_name' => $finishing->name,
            'quantity' => $simpleItem->quantity,
            'is_double_sided' => false,
            'unit_price' => $finishingCost / $simpleItem->quantity,
            'total_price' => $finishingCost,
        ]);
        
        $this->info("Acabado persistido en document_item_finishings");
        
        // Simular lo que hace la UI: recalcular totales del documento
        $this->info("Recalculando totales del documento (simulando UI)...");
        $document->recalculateTotals();
        
        // Verificar el resultado
        $documentItem->refresh();
        $document->refresh();
        $this->info("Verificación final:");
        $this->info("- DocumentItem total: $" . number_format($documentItem->total_price, 2));
        $this->info("- DocumentItem total (fresh from DB): $" . number_format(DocumentItem::find($documentItem->id)->total_price, 2));
        $this->info("- Acabados asociados: " . $documentItem->finishings()->count());
        $this->info("- Total del documento: $" . number_format($document->total, 2));
        
        $this->info('✅ Test completado exitosamente!');
    }
}

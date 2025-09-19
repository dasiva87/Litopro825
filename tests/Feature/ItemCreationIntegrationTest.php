<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\SimpleItem;
use App\Models\Product;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Models\Contact;
use App\Services\SimpleItemCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ItemCreationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;
    private Paper $paper;
    private PrintingMachine $machine;
    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->forCompany($this->company->id)->create();
        $this->paper = Paper::factory()->create(['company_id' => $this->company->id]);
        $this->machine = PrintingMachine::factory()->create(['company_id' => $this->company->id]);
        
        $this->document = Document::factory()->create([
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function simple_item_creation_triggers_automatic_calculations()
    {
        $itemData = [
            'description' => 'Tarjetas de presentación premium',
            'quantity' => 2000,
            'horizontal_size' => 9.0,
            'vertical_size' => 5.0,
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'ink_front_count' => 4,
            'ink_back_count' => 1,
            'front_back_plate' => false,
            'profit_percentage' => 35.0,
            'design_value' => 25000,
            'transport_value' => 15000
        ];

        $simpleItem = SimpleItem::create($itemData);

        // Verificar que se creó correctamente
        $this->assertDatabaseHas('simple_items', [
            'description' => 'Tarjetas de presentación premium',
            'quantity' => 2000
        ]);

        // Verificar que los cálculos automáticos funcionan
        $calculator = new SimpleItemCalculatorService();
        $pricing = $calculator->calculateFinalPricing($simpleItem);

        $this->assertGreaterThan(0, $pricing->finalPrice);
        $this->assertEquals(35.0, $pricing->profitPercentage);
        $this->assertGreaterThan(0, $pricing->mountingOption->cutsPerSheet);
        $this->assertGreaterThan(0, $pricing->printingCalculation->millaresFinal);
    }

    /** @test */
    public function simple_item_integrates_with_document_items_correctly()
    {
        $simpleItem = SimpleItem::factory()->businessCard()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 1000,
            'profit_percentage' => 30.0,
            'cutting_cost' => 0,  // Para activar cálculo automático
            'mounting_cost' => 0  // Para activar cálculo automático
        ]);

        // Calcular pricing
        $calculator = new SimpleItemCalculatorService();
        $pricing = $calculator->calculateFinalPricing($simpleItem);

        // Crear DocumentItem asociado
        $documentItem = DocumentItem::create([
            'document_id' => $this->document->id,
            'itemable_type' => 'App\\Models\\SimpleItem',
            'itemable_id' => $simpleItem->id,
            'description' => $simpleItem->description,
            'quantity' => $simpleItem->quantity,
            'unit_price' => $pricing->unitPrice,
            'total_price' => $pricing->finalPrice
        ]);

        // Verificar relación polimórfica
        $this->assertEquals($simpleItem->id, $documentItem->itemable->id);
        $this->assertEquals('App\\Models\\SimpleItem', $documentItem->itemable_type);
        $this->assertEquals(round($pricing->finalPrice, 2), (float) $documentItem->total_price);

        // Verificar que el documento puede acceder al item
        $this->assertEquals(1, $this->document->items()->count());
        $this->assertEquals($simpleItem->description, $this->document->items()->first()->description);
    }

    /** @test */
    public function product_stock_validation_during_document_item_creation()
    {
        $product = Product::factory()->create([
            'name' => 'Papel Bond A4',
            'stock' => 25,
            'min_stock' => 10,
            'sale_price' => 500
        ]);

        // Caso 1: Cantidad dentro del stock disponible
        $validQuantity = 20;
        $documentItem1 = DocumentItem::create([
            'document_id' => $this->document->id,
            'itemable_type' => 'App\\Models\\Product',
            'itemable_id' => $product->id,
            'description' => $product->name,
            'quantity' => $validQuantity,
            'unit_price' => $product->sale_price,
            'total_price' => $product->sale_price * $validQuantity
        ]);

        $this->assertTrue($product->hasStock($validQuantity));
        $this->assertEquals($validQuantity * $product->sale_price, $documentItem1->total_price);

        // Caso 2: Cantidad que excede el stock (se permite crear pero se identifica)
        $excessQuantity = 30;
        $documentItem2 = DocumentItem::create([
            'document_id' => $this->document->id,
            'itemable_type' => 'App\\Models\\Product',
            'itemable_id' => $product->id,
            'description' => $product->name,
            'quantity' => $excessQuantity,
            'unit_price' => $product->sale_price,
            'total_price' => $product->sale_price * $excessQuantity
        ]);

        $this->assertFalse($product->hasStock($excessQuantity));
        $this->assertTrue($product->isLowStock()); // Stock actual (25) < mínimo requerido para esta orden
    }

    /** @test */
    public function complex_quotation_with_mixed_item_types_calculates_correctly()
    {
        // Crear SimpleItem
        $simpleItem = SimpleItem::factory()->flyer()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 5000,
            'profit_percentage' => 25.0
        ]);

        $calculator = new SimpleItemCalculatorService();
        $simpleItemPricing = $calculator->calculateFinalPricing($simpleItem);

        // Crear Products
        $product1 = Product::factory()->create([
            'name' => 'Plastificado',
            'sale_price' => 15000,
            'stock' => 100
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Encuadernación',
            'sale_price' => 25000,
            'stock' => 50
        ]);

        // Agregar todos los items al documento
        DocumentItem::create([
            'document_id' => $this->document->id,
            'itemable_type' => 'App\\Models\\SimpleItem',
            'itemable_id' => $simpleItem->id,
            'description' => $simpleItem->description,
            'quantity' => $simpleItem->quantity,
            'unit_price' => $simpleItemPricing->unitPrice,
            'total_price' => $simpleItemPricing->finalPrice
        ]);

        DocumentItem::create([
            'document_id' => $this->document->id,
            'itemable_type' => 'App\\Models\\Product',
            'itemable_id' => $product1->id,
            'description' => $product1->name,
            'quantity' => 1,
            'unit_price' => $product1->sale_price,
            'total_price' => $product1->sale_price
        ]);

        DocumentItem::create([
            'document_id' => $this->document->id,
            'itemable_type' => 'App\\Models\\Product',
            'itemable_id' => $product2->id,
            'description' => $product2->name,
            'quantity' => 2,
            'unit_price' => $product2->sale_price,
            'total_price' => $product2->sale_price * 2
        ]);

        // Recalcular totales del documento
        $this->document->recalculateTotals();

        $expectedSubtotal = $simpleItemPricing->finalPrice + $product1->sale_price + ($product2->sale_price * 2);
        $expectedTax = $expectedSubtotal * ($this->document->tax_percentage / 100);
        $expectedTotal = $expectedSubtotal + $expectedTax;

        $this->assertEquals(3, $this->document->documentItems()->count());
        $this->assertEquals($expectedSubtotal, $this->document->subtotal);
        $this->assertEquals($expectedTax, $this->document->tax_amount);
        $this->assertEquals($expectedTotal, $this->document->total);
    }

    /** @test */
    public function simple_item_recalculation_after_parameter_changes()
    {
        $simpleItem = SimpleItem::factory()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 1000,
            'profit_percentage' => 20.0
        ]);

        $calculator = new SimpleItemCalculatorService();
        $initialPricing = $calculator->calculateFinalPricing($simpleItem);

        // Crear DocumentItem inicial
        $documentItem = DocumentItem::create([
            'document_id' => $this->document->id,
            'itemable_type' => 'App\\Models\\SimpleItem',
            'itemable_id' => $simpleItem->id,
            'description' => $simpleItem->description,
            'quantity' => $simpleItem->quantity,
            'unit_price' => $initialPricing->unitPrice,
            'total_price' => $initialPricing->finalPrice
        ]);

        // Cambiar parámetros del SimpleItem
        $simpleItem->update([
            'quantity' => 2000, // Duplicar cantidad
            'profit_percentage' => 30.0 // Aumentar margen
        ]);

        // Recalcular con nuevos parámetros
        $newPricing = $calculator->calculateFinalPricing($simpleItem->fresh());

        // El nuevo precio debe ser diferente
        $this->assertNotEquals($initialPricing->finalPrice, $newPricing->finalPrice);
        $this->assertEquals(30.0, $newPricing->profitPercentage);
        $this->assertEquals(2000, $simpleItem->fresh()->quantity);

        // Actualizar DocumentItem con nuevos valores
        $documentItem->update([
            'quantity' => $simpleItem->quantity,
            'unit_price' => $newPricing->unitPrice,
            'total_price' => $newPricing->finalPrice
        ]);

        $this->assertEquals($newPricing->finalPrice, $documentItem->fresh()->total_price);
    }

    /** @test */
    public function cutting_calculation_integration_with_different_paper_sizes()
    {
        // Crear papeles con diferentes tamaños
        $smallPaper = Paper::factory()->create([
            'name' => 'Carta',
            'width' => 70,
            'height' => 50,
            'price' => 800
        ]);

        $largePaper = Paper::factory()->create([
            'name' => 'Pliego',
            'width' => 100,
            'height' => 70,
            'price' => 1500
        ]);

        // Mismo item con diferentes papeles
        $itemSmallPaper = SimpleItem::factory()->businessCard()->create([
            'paper_id' => $smallPaper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 1000
        ]);

        $itemLargePaper = SimpleItem::factory()->businessCard()->create([
            'paper_id' => $largePaper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 1000
        ]);

        $calculator = new SimpleItemCalculatorService();
        
        $pricingSmall = $calculator->calculateFinalPricing($itemSmallPaper);
        $pricingLarge = $calculator->calculateFinalPricing($itemLargePaper);

        // El papel más grande debe permitir más cortes por pliego
        $this->assertGreaterThanOrEqual(
            $pricingSmall->mountingOption->cutsPerSheet,
            $pricingLarge->mountingOption->cutsPerSheet
        );

        // Pero puede que necesite menos pliegos en total
        $this->assertLessThanOrEqual(
            $pricingSmall->mountingOption->sheetsNeeded,
            $pricingLarge->mountingOption->sheetsNeeded
        );
    }

    /** @test */
    public function printing_machine_constraints_affect_calculations()
    {
        // Máquina con limitaciones
        $limitedMachine = PrintingMachine::factory()->create([
            'max_colors' => 2, // Solo 2 colores
            'max_width' => 60,
            'max_height' => 40,
            'cost_per_impression' => 100
        ]);

        // Máquina de alta capacidad
        $highCapacityMachine = PrintingMachine::factory()->highCapacity()->create([
            'cost_per_impression' => 300
        ]);

        // Item que requiere 4 colores
        $itemLimited = SimpleItem::factory()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $limitedMachine->id,
            'ink_front_count' => 4,
            'ink_back_count' => 0,
            'quantity' => 1000
        ]);

        $itemHighCapacity = SimpleItem::factory()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $highCapacityMachine->id,
            'ink_front_count' => 4,
            'ink_back_count' => 0,
            'quantity' => 1000
        ]);

        $calculator = new SimpleItemCalculatorService();

        // Validación técnica debe fallar para máquina limitada
        $validationLimited = $calculator->validateTechnicalViability($itemLimited);
        $this->assertFalse($validationLimited->isValid);
        $this->assertNotEmpty($validationLimited->errors);

        // Validación debe pasar para máquina de alta capacidad
        $validationHighCapacity = $calculator->validateTechnicalViability($itemHighCapacity);
        $this->assertTrue($validationHighCapacity->isValid);
        $this->assertEmpty($validationHighCapacity->errors);
    }

    /** @test */
    public function profit_margin_calculations_are_consistent()
    {
        $margins = [15.0, 25.0, 35.0, 50.0];
        $baseItem = SimpleItem::factory()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 1000,
            'profit_percentage' => 0 // Será actualizado en el loop
        ]);

        $calculator = new SimpleItemCalculatorService();
        $results = [];

        foreach ($margins as $margin) {
            $baseItem->update(['profit_percentage' => $margin]);
            $pricing = $calculator->calculateFinalPricing($baseItem->fresh());
            
            $results[$margin] = [
                'subtotal' => $pricing->subtotal,
                'profit_amount' => $pricing->profitAmount,
                'final_price' => $pricing->finalPrice
            ];

            // Verificar que el margen se aplicó correctamente
            $expectedProfitAmount = $pricing->subtotal * ($margin / 100);
            $this->assertEquals($expectedProfitAmount, $pricing->profitAmount, "Margin calculation failed for {$margin}%");
        }

        // Verificar que precios finales aumentan con el margen
        for ($i = 1; $i < count($margins); $i++) {
            $this->assertGreaterThan(
                $results[$margins[$i-1]]['final_price'],
                $results[$margins[$i]]['final_price'],
                "Price should increase with higher margins"
            );
        }
    }
}
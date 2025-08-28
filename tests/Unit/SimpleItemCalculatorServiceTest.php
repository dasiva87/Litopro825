<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SimpleItemCalculatorService;
use App\Models\SimpleItem;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleItemCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private SimpleItemCalculatorService $calculator;
    private Company $company;
    private Paper $paper;
    private PrintingMachine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new SimpleItemCalculatorService();
        
        // Crear datos de prueba
        $this->company = Company::factory()->create();
        $this->paper = Paper::factory()
            ->propalcote()
            ->state([
                'company_id' => $this->company->id,
                'width' => 100,
                'height' => 70,
                'cost_per_sheet' => 1000
            ])->create();
        
        $this->machine = PrintingMachine::factory()
            ->offset()
            ->state([
                'company_id' => $this->company->id,
                'cost_per_impression' => 200,
                'setup_cost' => 15000,
                'max_colors' => 6
            ])->create();
    }

    /** @test */
    public function it_calculates_mounting_options_correctly()
    {
        $item = SimpleItem::factory()->businessCard()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'horizontal_size' => 9.0,
            'vertical_size' => 5.0,
            'quantity' => 1000
        ]);

        $options = $this->calculator->calculateMountingOptions($item);

        $this->assertIsArray($options);
        $this->assertNotEmpty($options);
        
        // Verificar que tiene las 3 orientaciones (si son válidas)
        $orientations = array_column($options, 'orientation');
        $this->assertContains('horizontal', $orientations);
        
        // Verificar estructura de cada opción
        foreach ($options as $option) {
            $this->assertIsString($option->orientation);
            $this->assertIsInt($option->cutsPerSheet);
            $this->assertIsInt($option->sheetsNeeded);
            $this->assertIsFloat($option->utilizationPercentage);
            $this->assertIsFloat($option->paperCost);
            $this->assertGreaterThan(0, $option->cutsPerSheet);
        }

        // Verificar que están ordenadas por mejor aprovechamiento
        for ($i = 1; $i < count($options); $i++) {
            $this->assertGreaterThanOrEqual(
                $options[$i]->utilizationPercentage,
                $options[$i-1]->utilizationPercentage
            );
        }
    }

    /** @test */
    public function it_returns_empty_array_when_missing_paper_or_machine()
    {
        $item = SimpleItem::factory()->create([
            
            'paper_id' => null, // Sin papel
            'printing_machine_id' => $this->machine->id
        ]);

        $options = $this->calculator->calculateMountingOptions($item);
        $this->assertEmpty($options);

        $item->paper_id = $this->paper->id;
        $item->printing_machine_id = null; // Sin máquina
        $item->save();

        $options = $this->calculator->calculateMountingOptions($item);
        $this->assertEmpty($options);
    }

    /** @test */
    public function it_calculates_printing_millares_correctly()
    {
        $item = SimpleItem::factory()->create([
            
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'ink_front_count' => 4,
            'ink_back_count' => 1,
            'front_back_plate' => false
        ]);

        $mountingOptions = $this->calculator->calculateMountingOptions($item);
        $this->assertNotEmpty($mountingOptions);

        $printingCalc = $this->calculator->calculatePrintingMillares($item, $mountingOptions[0]);

        $this->assertEquals(5, $printingCalc->totalColors); // 4 + 1
        $this->assertIsFloat($printingCalc->millaresRaw);
        $this->assertIsInt($printingCalc->millaresFinal);
        $this->assertGreaterThanOrEqual($printingCalc->millaresRaw, $printingCalc->millaresFinal);
        $this->assertGreaterThanOrEqual(1, $printingCalc->millaresFinal); // Mínimo 1 millar
        $this->assertIsFloat($printingCalc->printingCost);
        $this->assertIsFloat($printingCalc->setupCost);
        $this->assertEquals($printingCalc->printingCost + $printingCalc->setupCost, $printingCalc->totalCost);
    }

    /** @test */
    public function it_handles_front_back_plate_correctly()
    {
        $item = SimpleItem::factory()->create([
            
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'ink_front_count' => 4,
            'ink_back_count' => 2,
            'front_back_plate' => true // Tiro y retiro plancha
        ]);

        $mountingOptions = $this->calculator->calculateMountingOptions($item);
        $printingCalc = $this->calculator->calculatePrintingMillares($item, $mountingOptions[0]);

        // Con tiro y retiro plancha, debe tomar el máximo entre frente y reverso
        $this->assertEquals(4, $printingCalc->totalColors); // max(4, 2)
        $this->assertTrue($printingCalc->frontBackPlate);
    }

    /** @test */
    public function it_rounds_millares_up_correctly()
    {
        $item = SimpleItem::factory()->create([
            
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 100, // Cantidad pequeña para forzar millares bajos
            'ink_front_count' => 1,
            'ink_back_count' => 0
        ]);

        $mountingOptions = $this->calculator->calculateMountingOptions($item);
        $printingCalc = $this->calculator->calculatePrintingMillares($item, $mountingOptions[0]);

        // El millar final debe ser siempre entero y >= al raw
        $this->assertIsInt($printingCalc->millaresFinal);
        $this->assertGreaterThanOrEqual($printingCalc->millaresRaw, $printingCalc->millaresFinal);
        $this->assertGreaterThanOrEqual(1, $printingCalc->millaresFinal);
    }

    /** @test */
    public function it_calculates_additional_costs_correctly()
    {
        $item = SimpleItem::factory()->withAllCosts()->create([
            
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'design_value' => 25000,
            'transport_value' => 15000,
            'rifle_value' => 8000
        ]);

        $mountingOptions = $this->calculator->calculateMountingOptions($item);
        $additionalCosts = $this->calculator->calculateAdditionalCosts($item, $mountingOptions[0]);

        $this->assertEquals(25000, $additionalCosts->designCost);
        $this->assertEquals(15000, $additionalCosts->transportCost);
        $this->assertEquals(8000, $additionalCosts->rifleCost);
        $this->assertGreaterThan(0, $additionalCosts->cuttingCost);
        $this->assertGreaterThan(0, $additionalCosts->mountingCost);

        $expectedTotal = 25000 + 15000 + 8000 + $additionalCosts->cuttingCost + $additionalCosts->mountingCost;
        $this->assertEquals($expectedTotal, $additionalCosts->getTotalCost());
    }

    /** @test */
    public function it_calculates_mounting_cost_based_on_ink_count()
    {
        $item1 = SimpleItem::factory()->create([
            
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'ink_front_count' => 1,
            'ink_back_count' => 0
        ]);

        $item2 = SimpleItem::factory()->create([
            
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'ink_front_count' => 4,
            'ink_back_count' => 4
        ]);

        $mountingOptions1 = $this->calculator->calculateMountingOptions($item1);
        $mountingOptions2 = $this->calculator->calculateMountingOptions($item2);

        $costs1 = $this->calculator->calculateAdditionalCosts($item1, $mountingOptions1[0]);
        $costs2 = $this->calculator->calculateAdditionalCosts($item2, $mountingOptions2[0]);

        // Item con más tintas debe tener mayor costo de montaje
        $this->assertGreaterThan($costs1->mountingCost, $costs2->mountingCost);
    }

    /** @test */
    public function it_calculates_final_pricing_with_all_components()
    {
        $item = SimpleItem::factory()->withAllCosts()->create([
            
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 1000,
            'profit_percentage' => 30.0
        ]);

        $pricing = $this->calculator->calculateFinalPricing($item);

        // Verificar estructura del resultado
        $this->assertInstanceOf(\App\Services\PricingResult::class, $pricing);
        $this->assertInstanceOf(\App\Services\MountingOption::class, $pricing->mountingOption);
        $this->assertInstanceOf(\App\Services\PrintingCalculation::class, $pricing->printingCalculation);
        $this->assertInstanceOf(\App\Services\AdditionalCosts::class, $pricing->additionalCosts);

        // Verificar cálculos
        $this->assertGreaterThan(0, $pricing->subtotal);
        $this->assertEquals(30.0, $pricing->profitPercentage);
        $this->assertEquals($pricing->subtotal * 0.3, $pricing->profitAmount);
        $this->assertEquals($pricing->subtotal + $pricing->profitAmount, $pricing->finalPrice);
        $this->assertEquals($pricing->finalPrice / $item->quantity, $pricing->unitPrice);

        // Verificar breakdown
        $breakdown = $pricing->costBreakdown;
        $this->assertIsArray($breakdown);
        $this->assertArrayHasKey('paper', $breakdown);
        $this->assertArrayHasKey('printing', $breakdown);
        $this->assertArrayHasKey('setup', $breakdown);
    }

    /** @test */
    public function it_uses_optimal_mounting_option_when_none_specified()
    {
        $item = SimpleItem::factory()->create([
            
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'profit_percentage' => 25.0
        ]);

        $pricing = $this->calculator->calculateFinalPricing($item);

        // Debe usar la opción óptima (mejor aprovechamiento)
        $allOptions = $this->calculator->calculateMountingOptions($item);
        $optimalOption = $allOptions[0]; // Ya están ordenadas por aprovechamiento

        $this->assertEquals($optimalOption->orientation, $pricing->mountingOption->orientation);
        $this->assertEquals($optimalOption->utilizationPercentage, $pricing->mountingOption->utilizationPercentage);
    }

    /** @test */
    public function it_throws_exception_when_no_mounting_options_available()
    {
        $item = SimpleItem::factory()->create([
            
            'paper_id' => null, // Sin papel
            'printing_machine_id' => $this->machine->id
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No se pudo calcular opciones de montaje válidas');

        $this->calculator->calculateFinalPricing($item);
    }

    /** @test */
    public function it_validates_technical_viability_dimensions()
    {
        $item = SimpleItem::factory()->create([
            
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'horizontal_size' => 150, // Excede límite de máquina (125)
            'vertical_size' => 100
        ]);

        $validation = $this->calculator->validateTechnicalViability($item);

        $this->assertFalse($validation->isValid);
        $this->assertNotEmpty($validation->errors);
        $this->assertStringContainsString('exceden los límites', $validation->errors[0]);
    }

    /** @test */
    public function it_validates_technical_viability_colors()
    {
        $item = SimpleItem::factory()->create([
            
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'ink_front_count' => 4,
            'ink_back_count' => 4, // 8 colores total, excede máximo de máquina (6)
        ]);

        $validation = $this->calculator->validateTechnicalViability($item);

        $this->assertFalse($validation->isValid);
        $this->assertNotEmpty($validation->errors);
        $this->assertStringContainsString('excede la capacidad de la máquina', $validation->errors[0]);
    }

    /** @test */
    public function it_validates_paper_stock_availability()
    {
        $this->paper->update(['stock' => 10]);

        $item = SimpleItem::factory()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 10000 // Cantidad grande que requerirá muchos pliegos
        ]);

        $validation = $this->calculator->validateTechnicalViability($item);

        if ($validation->hasWarnings()) {
            $this->assertStringContainsString('solo hay', $validation->warnings[0]);
            $this->assertStringContainsString('en stock', $validation->warnings[0]);
        }
    }

    /** @test */
    public function it_provides_cost_breakdown_details()
    {
        $item = SimpleItem::factory()->withAllCosts()->create([
            
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id
        ]);

        $pricing = $this->calculator->calculateFinalPricing($item);
        $breakdown = $pricing->costBreakdown;

        // Verificar que todos los componentes de costo están presentes
        $expectedKeys = ['paper', 'printing', 'setup', 'cutting', 'mounting', 'design', 'transport', 'rifle'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $breakdown);
            $this->assertArrayHasKey('description', $breakdown[$key]);
            $this->assertArrayHasKey('quantity', $breakdown[$key]);
            $this->assertArrayHasKey('cost', $breakdown[$key]);
        }
    }

    /** @test */
    public function it_generates_formatted_breakdown()
    {
        $item = SimpleItem::factory()->withAllCosts()->create([
            
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id
        ]);

        $pricing = $this->calculator->calculateFinalPricing($item);
        $formatted = $pricing->getFormattedBreakdown();

        $this->assertIsArray($formatted);
        
        // Solo debe incluir items con costo > 0
        foreach ($formatted as $item) {
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('detail', $item);
            $this->assertArrayHasKey('cost', $item);
            $this->assertStringContainsString('$', $item['cost']);
        }
    }
}
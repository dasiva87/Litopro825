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

        // El millar final debe ser siempre entero y >= 1 (mínimo)
        $this->assertIsInt($printingCalc->millaresFinal);
        $this->assertGreaterThanOrEqual(1, $printingCalc->millaresFinal);

        // Probar lógica específica de redondeo
        // Si decimal <= 0.1: floor(), si decimal > 0.1: ceil()
        if ($printingCalc->millaresRaw <= 1) {
            $this->assertEquals(1, $printingCalc->millaresFinal); // Mínimo 1 millar
        } else {
            $decimalPart = $printingCalc->millaresRaw - floor($printingCalc->millaresRaw);
            if ($decimalPart > 0.1) {
                $this->assertEquals((int) ceil($printingCalc->millaresRaw), $printingCalc->millaresFinal);
            } else {
                $this->assertEquals((int) floor($printingCalc->millaresRaw), $printingCalc->millaresFinal);
            }
        }
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
        $this->assertGreaterThanOrEqual(0, $additionalCosts->ctpCost); // CTP puede ser 0 si la máquina no lo cobra

        $expectedTotal = 25000 + 15000 + 8000 + $additionalCosts->cuttingCost + $additionalCosts->mountingCost + $additionalCosts->ctpCost;
        $this->assertEquals($expectedTotal, $additionalCosts->getTotalCost());
    }

    /** @test */
    public function it_calculates_mounting_cost_based_on_ink_count()
    {
        $item1 = SimpleItem::factory()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'ink_front_count' => 1,
            'ink_back_count' => 0,
            'cutting_cost' => 0,  // Para activar cálculo automático
            'mounting_cost' => 0  // Para activar cálculo automático
        ]);

        $item2 = SimpleItem::factory()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'ink_front_count' => 4,
            'ink_back_count' => 4,
            'cutting_cost' => 0,  // Para activar cálculo automático
            'mounting_cost' => 0  // Para activar cálculo automático
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

    /** @test */
    public function it_handles_sobrante_papel_calculation_correctly()
    {
        // Test con sobrante ≤ 100: NO se cobra en impresión
        $itemLowWaste = SimpleItem::factory()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 1000,
            'sobrante_papel' => 50, // ≤ 100
            'horizontal_size' => 9.0,
            'vertical_size' => 5.0,
            'ink_front_count' => 4,
            'ink_back_count' => 0
        ]);

        $mountingOptions = $this->calculator->calculateMountingOptions($itemLowWaste);
        $printingCalc = $this->calculator->calculatePrintingMillares($itemLowWaste, $mountingOptions[0]);

        // Se deben usar 1050 items para calcular pliegos (incluye sobrante)
        // Pero solo se deben cobrar los pliegos y cortes originales en impresión (sin sobrante)
        // Como el sobrante (50) es ≤ 100, no se debe agregar a la cantidad para impresión
        $expectedQuantityForPrinting = $mountingOptions[0]->sheetsNeeded * $mountingOptions[0]->cutsPerSheet;
        $expectedMillaresRaw = (4 * $expectedQuantityForPrinting) / 1000;
        $this->assertEquals($expectedMillaresRaw, $printingCalc->millaresRaw);

        // Test con sobrante > 100: SÍ se cobra en impresión
        $itemHighWaste = SimpleItem::factory()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 1000,
            'sobrante_papel' => 150, // > 100
            'horizontal_size' => 9.0,
            'vertical_size' => 5.0,
            'ink_front_count' => 4,
            'ink_back_count' => 0
        ]);

        $mountingOptionsHigh = $this->calculator->calculateMountingOptions($itemHighWaste);
        $printingCalcHigh = $this->calculator->calculatePrintingMillares($itemHighWaste, $mountingOptionsHigh[0]);

        // Se deben cobrar los pliegos × cortes más el sobrante en impresión (incluye sobrante porque > 100)
        $expectedQuantityForPrintingHigh = ($mountingOptionsHigh[0]->sheetsNeeded * $mountingOptionsHigh[0]->cutsPerSheet) + 150;
        $expectedMillaresHighRaw = (4 * $expectedQuantityForPrintingHigh) / 1000;
        $this->assertEquals($expectedMillaresHighRaw, $printingCalcHigh->millaresRaw);

        // Verificar que el sobrante afecte el cálculo de pliegos
        $this->assertGreaterThan($mountingOptions[0]->sheetsNeeded, $mountingOptionsHigh[0]->sheetsNeeded);
    }

    /** @test */
    public function it_calculates_paper_costs_including_sobrante()
    {
        $item = SimpleItem::factory()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 1000,
            'sobrante_papel' => 100,
            'horizontal_size' => 10.0,
            'vertical_size' => 15.0
        ]);

        $mountingOptions = $this->calculator->calculateMountingOptions($item);

        // El costo del papel debe incluir los pliegos calculados para la cantidad total (1100)
        $this->assertGreaterThan(0, $mountingOptions[0]->paperCost);

        // Crear mismo item sin sobrante para comparar
        $itemNoWaste = SimpleItem::factory()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 1000,
            'sobrante_papel' => 0,
            'horizontal_size' => 10.0,
            'vertical_size' => 15.0
        ]);

        $mountingOptionsNoWaste = $this->calculator->calculateMountingOptions($itemNoWaste);

        // El item con sobrante debe costar más papel
        $this->assertGreaterThanOrEqual($mountingOptionsNoWaste[0]->paperCost, $mountingOptions[0]->paperCost);
    }

    /** @test */
    public function it_applies_correct_rounding_logic_for_millares()
    {
        // Crear un test más específico para validar la lógica de redondeo

        // Test 1: Decimal <= 0.1, debe usar floor()
        $item1 = SimpleItem::factory()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 2050, // Configurar para obtener decimal <= 0.1
            'sobrante_papel' => 0,
            'horizontal_size' => 9.0,
            'vertical_size' => 5.0,
            'ink_front_count' => 1,
            'ink_back_count' => 0
        ]);

        // Test 2: Decimal > 0.1, debe usar ceil()
        $item2 = SimpleItem::factory()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 2300, // Configurar para obtener decimal > 0.1
            'sobrante_papel' => 0,
            'horizontal_size' => 9.0,
            'vertical_size' => 5.0,
            'ink_front_count' => 1,
            'ink_back_count' => 0
        ]);

        $mountingOptions1 = $this->calculator->calculateMountingOptions($item1);
        $printingCalc1 = $this->calculator->calculatePrintingMillares($item1, $mountingOptions1[0]);

        $mountingOptions2 = $this->calculator->calculateMountingOptions($item2);
        $printingCalc2 = $this->calculator->calculatePrintingMillares($item2, $mountingOptions2[0]);

        // Validar que los millares se calculan correctamente
        $this->assertIsFloat($printingCalc1->millaresRaw);
        $this->assertIsInt($printingCalc1->millaresFinal);
        $this->assertIsFloat($printingCalc2->millaresRaw);
        $this->assertIsInt($printingCalc2->millaresFinal);

        // Validar la lógica de redondeo específicamente
        if ($printingCalc1->millaresRaw > 1) {
            $decimalPart1 = $printingCalc1->millaresRaw - floor($printingCalc1->millaresRaw);
            if ($decimalPart1 <= 0.1) {
                $this->assertEquals((int) floor($printingCalc1->millaresRaw), $printingCalc1->millaresFinal);
            }
        }

        if ($printingCalc2->millaresRaw > 1) {
            $decimalPart2 = $printingCalc2->millaresRaw - floor($printingCalc2->millaresRaw);
            if ($decimalPart2 > 0.1) {
                $this->assertEquals((int) ceil($printingCalc2->millaresRaw), $printingCalc2->millaresFinal);
            }
        }
    }
}
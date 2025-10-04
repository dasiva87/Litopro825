<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\MagazineItem;
use App\Models\MagazinePage;
use App\Models\SimpleItem;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Models\Finishing;
use App\Services\MagazineCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MagazineCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private MagazineCalculatorService $service;
    private MagazineItem $magazine;
    private SimpleItem $simpleItem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MagazineCalculatorService();

        // Crear datos de prueba básicos
        $paper = Paper::factory()->create([
            'name' => 'Test Paper',
            'width' => 70,
            'height' => 100,
            'cost_per_sheet' => 1000
        ]);

        $machine = PrintingMachine::factory()->create([
            'name' => 'Test Machine',
            'cost_per_impression' => 100,
            'setup_cost' => 5000
        ]);

        $this->simpleItem = SimpleItem::factory()->create([
            'description' => 'Test Page',
            'quantity' => 1000,
            'horizontal_size' => 21,
            'vertical_size' => 29.7,
            'paper_id' => $paper->id,
            'printing_machine_id' => $machine->id,
            'ink_front_count' => 4,
            'ink_back_count' => 0,
        ]);

        // Forzar un precio específico para la prueba (bypass observers)
        \DB::table('simple_items')
            ->where('id', $this->simpleItem->id)
            ->update(['final_price' => 50000]);
        $this->simpleItem->refresh();

        $this->magazine = MagazineItem::factory()->create([
            'description' => 'Test Magazine',
            'quantity' => 100,
            'closed_width' => 21,
            'closed_height' => 29.7,
            'binding_type' => 'grapado',
            'binding_side' => 'izquierda',
            'design_value' => 10000,
            'transport_value' => 5000,
            'profit_percentage' => 25
        ]);

        // Agregar una página de prueba
        MagazinePage::create([
            'magazine_item_id' => $this->magazine->id,
            'simple_item_id' => $this->simpleItem->id,
            'page_type' => 'portada',
            'page_order' => 1,
            'page_quantity' => 1
        ]);
    }

    /** @test */
    public function it_can_calculate_pages_total_cost()
    {
        $result = $this->service->calculatePagesTotal($this->magazine);
        
        $this->assertEquals(50000, $result); // SimpleItem final_price * page_quantity (1)
    }

    /** @test */
    public function it_can_calculate_binding_cost()
    {
        $result = $this->service->calculateBindingCost($this->magazine);
        
        $this->assertGreaterThan(0, $result);
        $this->assertIsFloat($result);
    }

    /** @test */
    public function it_applies_complexity_factor_for_high_page_count()
    {
        // Agregar más páginas para probar el factor de complejidad
        MagazinePage::create([
            'magazine_item_id' => $this->magazine->id,
            'simple_item_id' => $this->simpleItem->id,
            'page_type' => 'interior',
            'page_order' => 2,
            'page_quantity' => 50 // Muchas páginas para activar factor de complejidad
        ]);

        $baseCost = $this->service->calculateBindingCost($this->magazine);
        $this->assertGreaterThan(0, $baseCost);
    }

    /** @test */
    public function it_can_calculate_assembly_cost()
    {
        $result = $this->service->calculateAssemblyCost($this->magazine);
        
        $this->assertGreaterThan(0, $result);
        $this->assertIsFloat($result);
    }

    /** @test */
    public function it_can_calculate_finishing_cost_with_finishings()
    {
        // Crear un finishing de prueba
        $finishing = Finishing::create([
            'company_id' => 1,
            'code' => 'TEST-001',
            'name' => 'Test Finishing',
            'description' => 'Test finishing description',
            'unit_price' => 100,
            'measurement_unit' => \App\Enums\FinishingMeasurementUnit::UNIDAD,
            'active' => true
        ]);

        // Asociar el finishing con la revista
        $this->magazine->finishings()->attach($finishing->id, [
            'quantity' => 100,
            'unit_cost' => 100,
            'total_cost' => 10000
        ]);

        $result = $this->service->calculateFinishingCost($this->magazine);
        
        $this->assertEquals(10000, $result);
    }

    /** @test */
    public function it_calculates_final_pricing_correctly()
    {
        $result = $this->service->calculateFinalPricing($this->magazine);
        
        $this->assertInstanceOf(\App\Services\MagazinePricingResult::class, $result);
        $this->assertEquals(50000, $result->pagesCost); // SimpleItem price
        $this->assertGreaterThan(0, $result->bindingCost);
        $this->assertGreaterThan(0, $result->assemblyCost);
        $this->assertEquals(0, $result->finishingCost); // No finishings
        $this->assertEquals(10000, $result->designValue);
        $this->assertEquals(5000, $result->transportValue);
        $this->assertGreaterThan(0, $result->totalCost);
        $this->assertGreaterThan($result->totalCost, $result->finalPrice); // Final price should include profit
    }

    /** @test */
    public function it_validates_technical_viability()
    {
        $result = $this->service->validateTechnicalViability($this->magazine);
        
        $this->assertInstanceOf(\App\Services\MagazineValidationResult::class, $result);
        $this->assertIsArray($result->errors);
        $this->assertIsArray($result->warnings);
        $this->assertIsBool($result->isValid);
    }

    /** @test */
    public function it_detects_invalid_dimensions()
    {
        $invalidMagazine = MagazineItem::factory()->create([
            'description' => 'Invalid Magazine',
            'quantity' => 100,
            'closed_width' => 0, // Invalid
            'closed_height' => 0, // Invalid
            'binding_type' => 'grapado',
            'binding_side' => 'izquierda'
        ]);

        $result = $this->service->validateTechnicalViability($invalidMagazine);
        
        $this->assertFalse($result->isValid);
        $this->assertNotEmpty($result->errors);
    }

    /** @test */
    public function it_warns_about_inappropriate_binding_for_page_count()
    {
        // Crear una revista con muchas páginas y encuadernación grapada
        $heavyMagazine = MagazineItem::factory()->create([
            'description' => 'Heavy Magazine',
            'quantity' => 100,
            'closed_width' => 21,
            'closed_height' => 29.7,
            'binding_type' => 'grapado',
            'binding_side' => 'izquierda'
        ]);

        // Agregar muchas páginas
        MagazinePage::create([
            'magazine_item_id' => $heavyMagazine->id,
            'simple_item_id' => $this->simpleItem->id,
            'page_type' => 'interior',
            'page_order' => 1,
            'page_quantity' => 100 // Muchas páginas
        ]);

        $result = $this->service->validateTechnicalViability($heavyMagazine);
        
        $this->assertFalse($result->isValid); // Debería fallar por exceso de páginas
        $this->assertNotEmpty($result->errors);
    }

    /** @test */
    public function it_provides_detailed_breakdown()
    {
        $breakdown = $this->service->getDetailedBreakdown($this->magazine);
        
        $this->assertIsArray($breakdown);
        $this->assertArrayHasKey('pages', $breakdown);
        $this->assertArrayHasKey('binding', $breakdown);
        $this->assertArrayHasKey('assembly', $breakdown);
        $this->assertArrayHasKey('finishings', $breakdown);
        $this->assertArrayHasKey('additional_costs', $breakdown);
        $this->assertArrayHasKey('summary', $breakdown);
        
        // Verificar estructura de summary
        $this->assertArrayHasKey('subtotal', $breakdown['summary']);
        $this->assertArrayHasKey('final_price', $breakdown['summary']);
        $this->assertArrayHasKey('unit_price', $breakdown['summary']);
    }

    /** @test */
    public function it_calculates_different_binding_position_factors()
    {
        $leftBinding = MagazineItem::factory()->create([
            'description' => 'Left Binding',
            'quantity' => 100,
            'closed_width' => 21,
            'closed_height' => 29.7,
            'binding_type' => 'grapado',
            'binding_side' => 'izquierda'
        ]);

        $topBinding = MagazineItem::factory()->create([
            'description' => 'Top Binding',
            'quantity' => 100,
            'closed_width' => 21,
            'closed_height' => 29.7,
            'binding_type' => 'grapado',
            'binding_side' => 'arriba'
        ]);

        $leftCost = $this->service->calculateBindingCost($leftBinding);
        $topCost = $this->service->calculateBindingCost($topBinding);
        
        // El costo de encuadernación arriba debería ser más alto
        $this->assertGreaterThan($leftCost, $topCost);
    }

    /** @test */
    public function it_handles_magazines_without_pages()
    {
        $emptyMagazine = MagazineItem::factory()->create([
            'description' => 'Empty Magazine',
            'quantity' => 100,
            'closed_width' => 21,
            'closed_height' => 29.7,
            'binding_type' => 'grapado',
            'binding_side' => 'izquierda'
        ]);

        $result = $this->service->calculateFinalPricing($emptyMagazine);
        
        $this->assertEquals(0, $result->pagesCost);
        $this->assertGreaterThan(0, $result->bindingCost);
        $this->assertGreaterThan(0, $result->assemblyCost);
    }
}
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\MagazineCalculatorService;
use App\Models\MagazineItem;
use App\Models\MagazinePage;
use App\Models\SimpleItem;
use App\Models\Company;
use App\Models\Paper;
use App\Models\PrintingMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MagazineCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private MagazineCalculatorService $calculator;
    private Company $company;
    private Paper $paper;
    private PrintingMachine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new MagazineCalculatorService();
        
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
    public function it_calculates_binding_cost_correctly()
    {
        $magazine = MagazineItem::factory()
            ->state([
                'company_id' => $this->company->id,
                'quantity' => 100,
                'binding_type' => 'grapado',
                'binding_side' => 'izquierda',
            ])->create();

        // Simular páginas
        $this->createMagazinePages($magazine, 10); // 10 páginas totales

        $bindingCost = $this->calculator->calculateBindingCost($magazine);
        
        // Grapado: 500 base * 100 qty * 1.0 complexity * 1.0 position = 50,000
        $expectedCost = 500 * 100 * 1.0 * 1.0;
        $this->assertEquals($expectedCost, $bindingCost);
    }

    /** @test */
    public function it_applies_complexity_factor_for_many_pages()
    {
        $magazine = MagazineItem::factory()
            ->state([
                'company_id' => $this->company->id,
                'quantity' => 50,
                'binding_type' => 'grapado',
                'binding_side' => 'izquierda',
            ])->create();

        // Simular muchas páginas (> 50)
        $this->createMagazinePages($magazine, 60); // 60 páginas totales

        $bindingCost = $this->calculator->calculateBindingCost($magazine);

        // Grapado: 500 base * 50 qty * 1.40 complexity (50-100 pages) * 1.0 position = 35,000
        $expectedCost = 500 * 50 * 1.40 * 1.0;
        $this->assertEquals($expectedCost, $bindingCost);
    }

    /** @test */
    public function it_applies_position_factor_for_complex_binding()
    {
        $magazine = MagazineItem::factory()
            ->state([
                'company_id' => $this->company->id,
                'quantity' => 100,
                'binding_type' => 'grapado',
                'binding_side' => 'arriba', // Más complejo
            ])->create();

        $this->createMagazinePages($magazine, 10);

        $bindingCost = $this->calculator->calculateBindingCost($magazine);

        // Grapado: 500 base * 100 qty * 1.0 complexity * 1.15 position (arriba) = 57,500
        $expectedCost = 500 * 100 * 1.0 * 1.15;
        $this->assertEquals($expectedCost, $bindingCost);
    }

    /** @test */
    public function it_calculates_assembly_cost_correctly()
    {
        $magazine = MagazineItem::factory()
            ->state([
                'company_id' => $this->company->id,
                'quantity' => 100,
            ])->create();

        $this->createMagazinePages($magazine, 12); // 12 páginas

        $assemblyCost = $this->calculator->calculateAssemblyCost($magazine);

        // Base: 300 * 100 qty * (1 + 12 * 0.02) pages factor * (1 + 3*0.1) variety = 300 * 100 * 1.24 * 1.3
        // 3 tipos de página: portada, interior, contraportada
        $expectedCost = 300 * 100 * (1 + 12 * 0.02) * (1 + 3 * 0.1);
        $this->assertEquals($expectedCost, $assemblyCost);
    }

    /** @test */
    public function it_applies_special_pages_factor()
    {
        $magazine = MagazineItem::factory()
            ->state([
                'company_id' => $this->company->id,
                'quantity' => 50,
            ])->create();

        // Crear páginas con insertos y separadores
        MagazinePage::factory()->state([
            'magazine_item_id' => $magazine->id,
            'page_type' => 'inserto',
            'page_quantity' => 2,
            'simple_item_id' => $this->createSimpleItem()->id,
        ])->create();

        MagazinePage::factory()->state([
            'magazine_item_id' => $magazine->id,
            'page_type' => 'separador',
            'page_quantity' => 1,
            'simple_item_id' => $this->createSimpleItem()->id,
        ])->create();

        $assemblyCost = $this->calculator->calculateAssemblyCost($magazine);

        // Variety factor: 1.0 + 2 types * 0.1 = 1.2 (inserto, separador)
        $pagesCount = 3; // 2 + 1
        $expectedCost = 300 * 50 * (1 + $pagesCount * 0.02) * 1.2;
        $this->assertEquals($expectedCost, $assemblyCost);
    }

    /** @test */
    public function it_calculates_final_pricing_correctly()
    {
        $magazine = MagazineItem::factory()
            ->state([
                'company_id' => $this->company->id,
                'quantity' => 100,
                'binding_type' => 'grapado',
                'binding_side' => 'izquierda',
                'design_value' => 10000,
                'transport_value' => 5000,
                'profit_percentage' => 25,
            ])->create();

        $this->createMagazinePages($magazine, 10);

        $result = $this->calculator->calculateFinalPricing($magazine);
        
        // Verificar que todos los componentes están presentes
        $this->assertGreaterThan(0, $result->bindingCost);
        $this->assertGreaterThan(0, $result->assemblyCost);
        $this->assertEquals(10000, $result->designValue);
        $this->assertEquals(5000, $result->transportValue);
        $this->assertEquals(25, $result->profitPercentage);
        
        // Verificar que el precio final incluye la ganancia
        $expectedFinalPrice = $result->totalCost * 1.25;
        $this->assertEquals($expectedFinalPrice, $result->finalPrice);
    }

    /** @test */
    public function it_validates_technical_viability()
    {
        // Revista válida básica
        $magazine = MagazineItem::factory()
            ->state([
                'company_id' => $this->company->id,
                'quantity' => 100,
                'closed_width' => 21,
                'closed_height' => 29.7,
                'binding_type' => 'grapado',
            ])->create();

        $this->createMagazinePages($magazine, 20);

        $validation = $this->calculator->validateTechnicalViability($magazine);
        
        $this->assertTrue($validation->isValid);
        $this->assertEmpty($validation->errors);
    }

    /** @test */
    public function it_detects_invalid_dimensions()
    {
        $magazine = MagazineItem::factory()
            ->state([
                'company_id' => $this->company->id,
                'closed_width' => 0, // Inválido
                'closed_height' => -5, // Inválido
            ])->create();

        $validation = $this->calculator->validateTechnicalViability($magazine);
        
        $this->assertFalse($validation->isValid);
        $this->assertContains('Las dimensiones de la revista deben ser mayores a 0', $validation->errors);
    }

    /** @test */
    public function it_warns_about_binding_limitations()
    {
        $magazine = MagazineItem::factory()
            ->state([
                'company_id' => $this->company->id,
                'binding_type' => 'grapado',
            ])->create();

        $this->createMagazinePages($magazine, 100); // Muchas páginas para grapado

        $validation = $this->calculator->validateTechnicalViability($magazine);

        // Changed from warnings to errors - exceeding binding limits is a hard constraint
        $this->assertFalse($validation->isValid);
        $this->assertContains('El grapado no es recomendable para más de 80 páginas', $validation->errors);
    }

    /** @test */
    public function it_generates_default_pages_configuration()
    {
        $magazine = MagazineItem::factory()
            ->state(['company_id' => $this->company->id])
            ->create();

        $defaultPages = $this->calculator->generateDefaultPages($magazine);
        
        $this->assertCount(3, $defaultPages);
        $this->assertEquals('portada', $defaultPages[0]['page_type']);
        $this->assertEquals('interior', $defaultPages[1]['page_type']);
        $this->assertEquals('contraportada', $defaultPages[2]['page_type']);
        
        $this->assertEquals(1, $defaultPages[0]['page_quantity']);
        $this->assertEquals(8, $defaultPages[1]['page_quantity']);
        $this->assertEquals(1, $defaultPages[2]['page_quantity']);
    }

    /** @test */
    public function it_suggests_default_finishings()
    {
        $finishings = $this->calculator->suggestDefaultFinishings();
        
        $this->assertArrayHasKey('doblez', $finishings);
        $this->assertArrayHasKey('barniz', $finishings);
        $this->assertArrayHasKey('laminado', $finishings);
        $this->assertArrayHasKey('perforacion', $finishings);
        
        // El doblez debe estar recomendado
        $this->assertTrue($finishings['doblez']['recommended']);
        $this->assertFalse($finishings['barniz']['recommended']);
    }

    /** @test */
    public function it_provides_detailed_cost_breakdown()
    {
        $magazine = MagazineItem::factory()
            ->state([
                'company_id' => $this->company->id,
                'quantity' => 50,
            ])->create();

        $this->createMagazinePages($magazine, 12);

        $breakdown = $this->calculator->getDetailedBreakdown($magazine);
        
        $this->assertArrayHasKey('metrics', $breakdown);
        $this->assertArrayHasKey('pages', $breakdown);
        $this->assertArrayHasKey('binding', $breakdown);
        $this->assertArrayHasKey('assembly', $breakdown);
        $this->assertArrayHasKey('finishings', $breakdown);
        $this->assertArrayHasKey('summary', $breakdown);
        
        $this->assertEquals(12, $breakdown['metrics']['total_pages']);
        $this->assertGreaterThan(0, $breakdown['binding']['total']);
        $this->assertGreaterThan(0, $breakdown['assembly']['total']);
    }

    private function createMagazinePages(MagazineItem $magazine, int $totalPages): void
    {
        // Crear página de portada
        MagazinePage::factory()->state([
            'magazine_item_id' => $magazine->id,
            'page_type' => 'portada',
            'page_quantity' => 1,
            'simple_item_id' => $this->createSimpleItem()->id,
        ])->create();

        // Crear páginas interiores
        if ($totalPages > 2) {
            MagazinePage::factory()->state([
                'magazine_item_id' => $magazine->id,
                'page_type' => 'interior',
                'page_quantity' => $totalPages - 2, // Menos portada y contraportada
                'simple_item_id' => $this->createSimpleItem()->id,
            ])->create();
        }

        // Crear contraportada si hay más de 1 página
        if ($totalPages > 1) {
            MagazinePage::factory()->state([
                'magazine_item_id' => $magazine->id,
                'page_type' => 'contraportada',
                'page_quantity' => 1,
                'simple_item_id' => $this->createSimpleItem()->id,
            ])->create();
        }
    }

    private function createSimpleItem(): SimpleItem
    {
        return SimpleItem::factory()
            ->state([
                'company_id' => $this->company->id,
                'paper_id' => $this->paper->id,
                'printing_machine_id' => $this->machine->id,
                'quantity' => 100,
                'final_price' => 5000,
            ])->create();
    }
}
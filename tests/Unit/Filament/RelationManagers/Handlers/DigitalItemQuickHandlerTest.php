<?php

namespace Tests\Unit\Filament\RelationManagers\Handlers;

use App\Filament\Resources\Documents\RelationManagers\Handlers\DigitalItemQuickHandler;
use App\Models\Company;
use App\Models\DigitalItem;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Finishing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalItemQuickHandlerTest extends TestCase
{
    use RefreshDatabase;

    private DigitalItemQuickHandler $handler;
    private User $user;
    private Company $company;
    private Document $document;
    private DigitalItem $digitalItem;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear datos de prueba
        $this->company = Company::factory()->create(['company_type' => 'litografia']);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->document = Document::factory()->create(['company_id' => $this->company->id]);

        // Crear item digital de prueba
        $this->digitalItem = DigitalItem::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'DIG001',
            'description' => 'Test Digital Item',
            'pricing_type' => 'unit',
            'unit_value' => 50.00,
            'active' => true
        ]);

        // Autenticar usuario
        $this->actingAs($this->user);

        // Configurar contexto tenant
        config(['app.current_tenant_id' => $this->company->id]);

        $this->handler = new DigitalItemQuickHandler();
    }

    /** @test */
    public function it_implements_quick_action_handler_interface()
    {
        $this->assertInstanceOf(
            \App\Filament\Resources\Documents\RelationManagers\Contracts\QuickActionHandlerInterface::class,
            $this->handler
        );
    }

    /** @test */
    public function it_returns_correct_metadata()
    {
        $this->assertEquals('Item Digital Rápido', $this->handler->getLabel());
        $this->assertEquals('heroicon-o-computer-desktop', $this->handler->getIcon());
        $this->assertEquals('primary', $this->handler->getColor());
        $this->assertEquals('5xl', $this->handler->getModalWidth());
        $this->assertEquals('Item digital agregado correctamente', $this->handler->getSuccessNotificationTitle());
    }

    /** @test */
    public function it_is_visible_only_for_litografia_companies()
    {
        // Visible para litografías
        $this->assertTrue($this->handler->isVisible());

        // No visible para papelerías
        $this->company->update(['company_type' => 'papeleria']);
        $this->assertFalse($this->handler->isVisible());
    }

    /** @test */
    public function it_returns_form_schema_array()
    {
        $schema = $this->handler->getFormSchema();

        $this->assertIsArray($schema);
        $this->assertNotEmpty($schema);
    }

    /** @test */
    public function it_creates_document_item_for_unit_pricing()
    {
        $data = [
            'digital_item_id' => $this->digitalItem->id,
            'quantity' => 3
        ];

        // Verificar estado inicial
        $this->assertEquals(0, DocumentItem::count());

        // Ejecutar handler
        $this->handler->handleCreate($data, $this->document);

        // Verificar que se creó el DocumentItem
        $this->assertEquals(1, DocumentItem::count());

        $documentItem = DocumentItem::first();
        $this->assertEquals('App\\Models\\DigitalItem', $documentItem->itemable_type);
        $this->assertEquals($this->digitalItem->id, $documentItem->itemable_id);
        $this->assertEquals('Digital: Test Digital Item', $documentItem->description);
        $this->assertEquals(3, $documentItem->quantity);
        $this->assertEquals('digital', $documentItem->item_type);

        // Verificar configuración del item
        $itemConfig = json_decode($documentItem->item_config, true);
        $this->assertEquals('unit', $itemConfig['pricing_type']);
        $this->assertEquals(50.00, $itemConfig['unit_value']);

        // Verificar precios calculados (3 * 50.00 = 150.00)
        $this->assertEquals(50.00, $documentItem->unit_price);
        $this->assertEquals(150.00, $documentItem->total_price);
    }

    /** @test */
    public function it_creates_document_item_for_size_pricing()
    {
        // Crear item digital con pricing por tamaño
        $sizeDigitalItem = DigitalItem::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'DIG002',
            'description' => 'Size-based Digital Item',
            'pricing_type' => 'size',
            'unit_value' => 100.00, // Por m²
            'active' => true
        ]);

        $data = [
            'digital_item_id' => $sizeDigitalItem->id,
            'quantity' => 2,
            'width' => 100, // 1 metro
            'height' => 50  // 0.5 metros
        ];

        $this->handler->handleCreate($data, $this->document);

        $documentItem = DocumentItem::first();
        $this->assertEquals($sizeDigitalItem->id, $documentItem->itemable_id);
        $this->assertEquals(2, $documentItem->quantity);

        // Verificar configuración con dimensiones
        $itemConfig = json_decode($documentItem->item_config, true);
        $this->assertEquals('size', $itemConfig['pricing_type']);
        $this->assertEquals(100, $itemConfig['width']);
        $this->assertEquals(50, $itemConfig['height']);
    }

    /** @test */
    public function it_validates_digital_item_exists()
    {
        $data = [
            'digital_item_id' => 99999, // ID que no existe
            'quantity' => 1
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Item digital no encontrado');

        $this->handler->handleCreate($data, $this->document);
    }

    /** @test */
    public function it_validates_required_parameters_for_size_pricing()
    {
        // Crear item digital con pricing por tamaño
        $sizeDigitalItem = DigitalItem::factory()->create([
            'company_id' => $this->company->id,
            'pricing_type' => 'size',
            'unit_value' => 100.00,
            'active' => true
        ]);

        $data = [
            'digital_item_id' => $sizeDigitalItem->id,
            'quantity' => 1
            // Faltan width y height requeridos para size pricing
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Parámetros inválidos');

        $this->handler->handleCreate($data, $this->document);
    }

    /** @test */
    public function it_handles_finishings_correctly()
    {
        // Crear acabado de prueba
        $finishing = Finishing::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Finishing',
            'active' => true
        ]);

        $data = [
            'digital_item_id' => $this->digitalItem->id,
            'quantity' => 2,
            'finishings' => [
                [
                    'finishing_id' => $finishing->id,
                    'quantity' => 1,
                    'calculated_cost' => 25.00
                ]
            ]
        ];

        $this->handler->handleCreate($data, $this->document);

        $documentItem = DocumentItem::first();

        // Precio base: 2 * 50.00 = 100.00
        // Acabados: 25.00
        // Total: 125.00
        // Unit price: 125.00 / 2 = 62.50
        $this->assertEquals(62.50, $documentItem->unit_price);
        $this->assertEquals(125.00, $documentItem->total_price);

        // Verificar que se creó el acabado relacionado
        $this->assertEquals(1, $documentItem->finishings()->count());
        $documentFinishing = $documentItem->finishings()->first();
        // DocumentItemFinishing stores name, not ID
        $this->assertEquals($finishing->name, $documentFinishing->finishing_name);
        $this->assertEquals(25.00, $documentFinishing->total_price);
    }

    /** @test */
    public function it_handles_multiple_finishings()
    {
        // Crear múltiples acabados
        $finishing1 = Finishing::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Finishing 1',
            'active' => true
        ]);

        $finishing2 = Finishing::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Finishing 2',
            'active' => true
        ]);

        $data = [
            'digital_item_id' => $this->digitalItem->id,
            'quantity' => 1,
            'finishings' => [
                [
                    'finishing_id' => $finishing1->id,
                    'quantity' => 1,
                    'calculated_cost' => 20.00
                ],
                [
                    'finishing_id' => $finishing2->id,
                    'quantity' => 2,
                    'calculated_cost' => 15.00
                ]
            ]
        ];

        $this->handler->handleCreate($data, $this->document);

        $documentItem = DocumentItem::first();

        // Precio base: 1 * 50.00 = 50.00
        // Acabados: 20.00 + 15.00 = 35.00
        // Total: 85.00
        $this->assertEquals(85.00, $documentItem->unit_price);
        $this->assertEquals(85.00, $documentItem->total_price);

        // Verificar que se crearon ambos acabados
        $this->assertEquals(2, $documentItem->finishings()->count());
    }

    /** @test */
    public function it_provides_digital_item_options()
    {
        // Crear items adicionales
        $activeItem = DigitalItem::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'DIG003',
            'description' => 'Active Digital Item',
            'active' => true
        ]);

        $inactiveItem = DigitalItem::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'DIG004',
            'description' => 'Inactive Digital Item',
            'active' => false
        ]);

        $options = $this->invokeMethod($this->handler, 'getDigitalItemOptions');

        // Debe incluir items activos
        $this->assertArrayHasKey($this->digitalItem->id, $options);
        $this->assertArrayHasKey($activeItem->id, $options);

        // No debe incluir items inactivos
        $this->assertArrayNotHasKey($inactiveItem->id, $options);

        // Verificar formato de las opciones
        $this->assertStringContainsString('DIG001', $options[$this->digitalItem->id]);
        $this->assertStringContainsString('Test Digital Item', $options[$this->digitalItem->id]);
    }

    /** @test */
    public function it_provides_finishing_options()
    {
        // Crear acabados
        $activeFinishing = Finishing::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Active Finishing',
            'active' => true
        ]);

        $inactiveFinishing = Finishing::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Inactive Finishing',
            'active' => false
        ]);

        $options = $this->invokeMethod($this->handler, 'getFinishingOptions');

        // Debe incluir acabados activos
        $this->assertArrayHasKey($activeFinishing->id, $options);

        // No debe incluir acabados inactivos
        $this->assertArrayNotHasKey($inactiveFinishing->id, $options);
    }

    /** @test */
    public function it_calculates_summary_correctly()
    {
        $getData = [
            'digital_item_id' => $this->digitalItem->id,
            'quantity' => 4
        ];

        // Crear callback que simula Filament's $get
        $get = function ($key) use ($getData) {
            return $getData[$key] ?? null;
        };

        $summary = $this->invokeMethod($this->handler, 'getCalculationSummary', [$get]);

        $this->assertStringContainsString('Test Digital Item', $summary);
        $this->assertStringContainsString('$50.00', $summary); // Unit price
        $this->assertStringContainsString('$200.00', $summary); // Total price (4 * 50)
        $this->assertStringContainsString('✅ Cálculo válido', $summary);
    }

    /** @test */
    public function it_shows_validation_errors_in_summary()
    {
        // Crear item con pricing por tamaño
        $sizeItem = DigitalItem::factory()->create([
            'company_id' => $this->company->id,
            'pricing_type' => 'size',
            'unit_value' => 100.00,
            'active' => true
        ]);

        $getData = [
            'digital_item_id' => $sizeItem->id,
            'quantity' => 1
            // Faltan dimensiones requeridas
        ];

        // Crear callback que simula Filament's $get
        $get = function ($key) use ($getData) {
            return $getData[$key] ?? null;
        };

        $summary = $this->invokeMethod($this->handler, 'getCalculationSummary', [$get]);

        $this->assertStringContainsString('❌', $summary);
        $this->assertStringNotContainsString('✅ Cálculo válido', $summary);
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
<?php

namespace Tests\Unit\Filament\RelationManagers\Handlers;

use App\Filament\Resources\Documents\RelationManagers\Handlers\CustomItemQuickHandler;
use App\Models\Company;
use App\Models\CustomItem;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomItemQuickHandlerTest extends TestCase
{
    use RefreshDatabase;

    private CustomItemQuickHandler $handler;
    private User $user;
    private Company $company;
    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear datos de prueba
        $this->company = Company::factory()->create(['company_type' => 'litografia']);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->document = Document::factory()->create(['company_id' => $this->company->id]);

        // Autenticar usuario
        $this->actingAs($this->user);

        // Configurar contexto tenant
        config(['app.current_tenant_id' => $this->company->id]);

        $this->handler = new CustomItemQuickHandler();
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
        $this->assertEquals('Item Personalizado Rápido', $this->handler->getLabel());
        $this->assertEquals('heroicon-o-pencil-square', $this->handler->getIcon());
        $this->assertEquals('secondary', $this->handler->getColor());
        $this->assertEquals('4xl', $this->handler->getModalWidth());
        $this->assertEquals('Item personalizado creado correctamente', $this->handler->getSuccessNotificationTitle());
    }

    /** @test */
    public function it_is_visible_for_litografia_companies()
    {
        $this->assertTrue($this->handler->isVisible());
    }

    /** @test */
    public function it_is_not_visible_for_papeleria_companies()
    {
        // Cambiar tipo de empresa
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
    public function it_creates_custom_item_and_document_item_successfully()
    {
        $data = [
            'description' => 'Test Custom Item',
            'quantity' => 5,
            'unit_price' => 100.00,
            'notes' => 'Test notes'
        ];

        // Verificar estado inicial
        $this->assertEquals(0, CustomItem::count());
        $this->assertEquals(0, DocumentItem::count());

        // Ejecutar handler
        $this->handler->handleCreate($data, $this->document);

        // Verificar que se crearon los registros
        $this->assertEquals(1, CustomItem::count());
        $this->assertEquals(1, DocumentItem::count());

        // Verificar datos del CustomItem
        $customItem = CustomItem::first();
        $this->assertEquals('Test Custom Item', $customItem->description);
        $this->assertEquals(5, $customItem->quantity);
        $this->assertEquals(100.00, $customItem->unit_price);
        $this->assertEquals('Test notes', $customItem->notes);
        $this->assertEquals(500.00, $customItem->total_price); // 5 * 100

        // Verificar datos del DocumentItem
        $documentItem = DocumentItem::first();
        $this->assertEquals('App\\Models\\CustomItem', $documentItem->itemable_type);
        $this->assertEquals($customItem->id, $documentItem->itemable_id);
        $this->assertEquals('Personalizado: Test Custom Item', $documentItem->description);
        $this->assertEquals(5, $documentItem->quantity);
        $this->assertEquals('100.00', $documentItem->unit_price); // Cast to decimal:2
        $this->assertEquals('500.00', $documentItem->total_price); // Cast to decimal:2
        $this->assertEquals($this->document->id, $documentItem->document_id);
    }

    /** @test */
    public function it_handles_optional_notes_field()
    {
        $dataWithoutNotes = [
            'description' => 'Test without notes',
            'quantity' => 1,
            'unit_price' => 50.00
        ];

        $this->handler->handleCreate($dataWithoutNotes, $this->document);

        $customItem = CustomItem::first();
        $this->assertNull($customItem->notes);
    }

    /** @test */
    public function it_calculates_total_price_correctly()
    {
        $testCases = [
            ['quantity' => 1, 'unit_price' => 100.00, 'expected_total' => 100.00],
            ['quantity' => 10, 'unit_price' => 25.50, 'expected_total' => 255.00],
            ['quantity' => 3, 'unit_price' => 33.33, 'expected_total' => 99.99],
        ];

        foreach ($testCases as $index => $case) {
            $data = [
                'description' => "Test Item {$index}",
                'quantity' => $case['quantity'],
                'unit_price' => $case['unit_price']
            ];

            $document = Document::factory()->create(['company_id' => $this->company->id]);
            $this->handler->handleCreate($data, $document);

            $customItem = CustomItem::where('description', "Test Item {$index}")->first();
            $this->assertEquals($case['expected_total'], $customItem->total_price,
                "Failed for quantity: {$case['quantity']}, unit_price: {$case['unit_price']}");
        }
    }

    /** @test */
    public function it_handles_decimal_prices_correctly()
    {
        $data = [
            'description' => 'Decimal Price Test',
            'quantity' => 7,
            'unit_price' => 12.347 // Número con muchos decimales
        ];

        $this->handler->handleCreate($data, $this->document);

        $customItem = CustomItem::first();
        $documentItem = DocumentItem::first();

        // Verificar que los precios se manejan correctamente (con redondeo decimal:2)
        $this->assertEquals('12.35', $customItem->unit_price); // Redondeado a 2 decimales
        $this->assertEquals('86.45', $customItem->total_price); // 7 * 12.35 (cast aplicado antes del observer)
        $this->assertEquals('12.35', $documentItem->unit_price);
        $this->assertEquals('86.45', $documentItem->total_price);
    }

    /** @test */
    public function it_requires_minimum_fields()
    {
        $this->expectException(\Exception::class);

        // Intentar crear sin campos requeridos
        $incompleteData = [
            'description' => 'Test'
            // Faltan quantity y unit_price
        ];

        $this->handler->handleCreate($incompleteData, $this->document);
    }

    /** @test */
    public function it_associates_with_correct_document()
    {
        $document1 = Document::factory()->create(['company_id' => $this->company->id]);
        $document2 = Document::factory()->create(['company_id' => $this->company->id]);

        $data = [
            'description' => 'Test Association',
            'quantity' => 1,
            'unit_price' => 100.00
        ];

        // Crear en documento 1
        $this->handler->handleCreate($data, $document1);

        $documentItem = DocumentItem::first();
        $this->assertEquals($document1->id, $documentItem->document_id);
        $this->assertNotEquals($document2->id, $documentItem->document_id);
    }
}
<?php

namespace Tests\Unit\Filament\RelationManagers\Handlers;

use App\Filament\Resources\Documents\RelationManagers\Handlers\ProductQuickHandler;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Product;
use App\Models\SupplierRelationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductQuickHandlerTest extends TestCase
{
    use RefreshDatabase;

    private ProductQuickHandler $handler;
    private User $user;
    private Company $company;
    private Document $document;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear datos de prueba
        $this->company = Company::factory()->create(['type' => 'litografia']);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->document = Document::factory()->create(['company_id' => $this->company->id]);

        // Crear producto de prueba
        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Product',
            'sale_price' => 100.00,
            'stock' => 50,
            'min_stock' => 10,
            'active' => true
        ]);

        // Autenticar usuario
        $this->actingAs($this->user);

        // Configurar contexto tenant
        config(['app.current_tenant_id' => $this->company->id]);

        $this->handler = new ProductQuickHandler();
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
        $this->assertEquals('Producto Rápido', $this->handler->getLabel());
        $this->assertEquals('heroicon-o-cube', $this->handler->getIcon());
        $this->assertEquals('purple', $this->handler->getColor());
        $this->assertEquals('5xl', $this->handler->getModalWidth());
        $this->assertEquals('Producto agregado correctamente', $this->handler->getSuccessNotificationTitle());
    }

    /** @test */
    public function it_is_visible_for_all_company_types()
    {
        // Visible para litografías
        $this->assertTrue($this->handler->isVisible());

        // Visible para papelerías
        $this->company->update(['type' => 'papeleria']);
        $this->assertTrue($this->handler->isVisible());
    }

    /** @test */
    public function it_returns_form_schema_array()
    {
        $schema = $this->handler->getFormSchema();

        $this->assertIsArray($schema);
        $this->assertNotEmpty($schema);
    }

    /** @test */
    public function it_creates_document_item_successfully()
    {
        $data = [
            'product_id' => $this->product->id,
            'quantity' => 5,
            'profit_margin' => 25, // 25%
        ];

        // Verificar estado inicial
        $this->assertEquals(0, DocumentItem::count());

        // Ejecutar handler
        $this->handler->handleCreate($data, $this->document);

        // Verificar que se creó el DocumentItem
        $this->assertEquals(1, DocumentItem::count());

        $documentItem = DocumentItem::first();
        $this->assertEquals('App\\Models\\Product', $documentItem->itemable_type);
        $this->assertEquals($this->product->id, $documentItem->itemable_id);
        $this->assertEquals('Producto: Test Product', $documentItem->description);
        $this->assertEquals(5, $documentItem->quantity);
        $this->assertEquals(25, $documentItem->profit_margin);

        // Verificar cálculo de precios con margen
        $expectedBaseTotal = 100.00 * 5; // $500
        $expectedTotalWithMargin = $expectedBaseTotal * 1.25; // $625
        $expectedUnitPriceWithMargin = $expectedTotalWithMargin / 5; // $125

        $this->assertEquals($expectedUnitPriceWithMargin, $documentItem->unit_price);
        $this->assertEquals($expectedTotalWithMargin, $documentItem->total_price);
    }

    /** @test */
    public function it_calculates_profit_margin_correctly()
    {
        $testCases = [
            ['margin' => 0, 'expected_multiplier' => 1.0],
            ['margin' => 25, 'expected_multiplier' => 1.25],
            ['margin' => 50, 'expected_multiplier' => 1.5],
            ['margin' => 100, 'expected_multiplier' => 2.0],
        ];

        foreach ($testCases as $index => $case) {
            $data = [
                'product_id' => $this->product->id,
                'quantity' => 2,
                'profit_margin' => $case['margin'],
            ];

            $document = Document::factory()->create(['company_id' => $this->company->id]);
            $this->handler->handleCreate($data, $document);

            $documentItem = DocumentItem::where('document_id', $document->id)->first();

            $expectedBaseTotal = 100.00 * 2; // $200
            $expectedTotalWithMargin = $expectedBaseTotal * $case['expected_multiplier'];
            $expectedUnitPriceWithMargin = $expectedTotalWithMargin / 2;

            $this->assertEquals($expectedUnitPriceWithMargin, $documentItem->unit_price,
                "Failed unit price for margin: {$case['margin']}%");
            $this->assertEquals($expectedTotalWithMargin, $documentItem->total_price,
                "Failed total price for margin: {$case['margin']}%");
        }
    }

    /** @test */
    public function it_validates_stock_availability()
    {
        // Producto con stock limitado
        $this->product->update(['stock' => 10]);

        // Intentar usar más stock del disponible
        $data = [
            'product_id' => $this->product->id,
            'quantity' => 15, // Más que el stock de 10
            'profit_margin' => 0,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $this->handler->handleCreate($data, $this->document);
    }

    /** @test */
    public function it_validates_product_exists()
    {
        $data = [
            'product_id' => 99999, // ID que no existe
            'quantity' => 1,
            'profit_margin' => 0,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Producto no encontrado');

        $this->handler->handleCreate($data, $this->document);
    }

    /** @test */
    public function it_handles_zero_profit_margin()
    {
        $data = [
            'product_id' => $this->product->id,
            'quantity' => 3,
            'profit_margin' => 0,
        ];

        $this->handler->handleCreate($data, $this->document);

        $documentItem = DocumentItem::first();

        // Sin margen, los precios deben ser iguales al precio base
        $this->assertEquals(100.00, $documentItem->unit_price);
        $this->assertEquals(300.00, $documentItem->total_price);
        $this->assertEquals(0, $documentItem->profit_margin);
    }

    /** @test */
    public function it_provides_product_options_for_litografia()
    {
        // Crear productos adicionales
        $activeProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Active Product',
            'active' => true,
            'stock' => 100
        ]);

        $inactiveProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Inactive Product',
            'active' => false
        ]);

        // Obtener opciones (método privado, pero podemos testear a través del comportamiento)
        $options = $this->invokeMethod($this->handler, 'getProductOptions');

        // Debe incluir productos activos
        $this->assertArrayHasKey($this->product->id, $options);
        $this->assertArrayHasKey($activeProduct->id, $options);

        // No debe incluir productos inactivos
        $this->assertArrayNotHasKey($inactiveProduct->id, $options);

        // Verificar formato de las opciones
        $this->assertStringContains('Test Product', $options[$this->product->id]);
        $this->assertStringContains('$100.00', $options[$this->product->id]);
        $this->assertStringContains('Stock: 50', $options[$this->product->id]);
    }

    /** @test */
    public function it_shows_low_stock_warning_in_options()
    {
        // Crear producto con stock bajo
        $lowStockProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Low Stock Product',
            'active' => true,
            'stock' => 5,
            'min_stock' => 10
        ]);

        $options = $this->invokeMethod($this->handler, 'getProductOptions');

        $this->assertStringContains('(STOCK BAJO)', $options[$lowStockProduct->id]);
    }

    /** @test */
    public function it_shows_no_stock_warning_in_options()
    {
        // Crear producto sin stock
        $noStockProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'No Stock Product',
            'active' => true,
            'stock' => 0
        ]);

        $options = $this->invokeMethod($this->handler, 'getProductOptions');

        $this->assertStringContains('(SIN STOCK)', $options[$noStockProduct->id]);
    }

    /** @test */
    public function it_includes_supplier_products_for_litografia()
    {
        // Crear empresa proveedora
        $supplierCompany = Company::factory()->create(['type' => 'litografia']);

        // Crear relación de proveedor aprobado
        SupplierRelationship::factory()->create([
            'client_company_id' => $this->company->id,
            'supplier_company_id' => $supplierCompany->id,
            'is_active' => true,
            'approved_at' => now()
        ]);

        // Crear producto del proveedor
        $supplierProduct = Product::factory()->create([
            'company_id' => $supplierCompany->id,
            'name' => 'Supplier Product',
            'active' => true,
            'stock' => 20
        ]);

        $options = $this->invokeMethod($this->handler, 'getProductOptions');

        // Debe incluir productos del proveedor
        $this->assertArrayHasKey($supplierProduct->id, $options);
        $this->assertStringContains($supplierCompany->name, $options[$supplierProduct->id]);
    }

    /** @test */
    public function it_excludes_non_approved_supplier_products()
    {
        // Crear empresa proveedora
        $supplierCompany = Company::factory()->create(['type' => 'litografia']);

        // Crear relación NO aprobada
        SupplierRelationship::factory()->create([
            'client_company_id' => $this->company->id,
            'supplier_company_id' => $supplierCompany->id,
            'is_active' => true,
            'approved_at' => null // No aprobado
        ]);

        // Crear producto del proveedor
        $supplierProduct = Product::factory()->create([
            'company_id' => $supplierCompany->id,
            'name' => 'Non-approved Supplier Product',
            'active' => true
        ]);

        $options = $this->invokeMethod($this->handler, 'getProductOptions');

        // NO debe incluir productos de proveedores no aprobados
        $this->assertArrayNotHasKey($supplierProduct->id, $options);
    }

    /** @test */
    public function it_provides_stock_info_correctly()
    {
        $testData = [
            'product_id' => $this->product->id,
            'quantity' => 20
        ];

        $stockInfo = $this->invokeMethod($this->handler, 'getStockInfo', [$testData]);

        $this->assertStringContains('Test Product', $stockInfo);
        $this->assertStringContains('$100.00', $stockInfo);
        $this->assertStringContains('50 unidades', $stockInfo);
        $this->assertStringContains('✅ Stock suficiente', $stockInfo);
    }

    /** @test */
    public function it_shows_stock_warning_when_quantity_exceeds_available()
    {
        $testData = [
            'product_id' => $this->product->id,
            'quantity' => 60 // Más que el stock de 50
        ];

        $stockInfo = $this->invokeMethod($this->handler, 'getStockInfo', [$testData]);

        $this->assertStringContains('⚠️ Stock insuficiente', $stockInfo);
        $this->assertStringContains('Solo hay 50 unidades', $stockInfo);
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
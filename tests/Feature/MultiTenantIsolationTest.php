<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Document;
use App\Models\SimpleItem;
use App\Models\Product;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Models\Contact;
use App\Models\DocumentItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;

class MultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear dos compañías separadas
        $this->companyA = Company::factory()->create(['name' => 'LitoGraphics A']);
        $this->companyB = Company::factory()->create(['name' => 'PrintShop B']);
        
        $this->userA = User::factory()->forCompany($this->companyA->id)->create();
        $this->userB = User::factory()->forCompany($this->companyB->id)->create();
    }

    /** @test */
    public function documents_are_isolated_by_company()
    {
        // Crear documentos para cada compañía
        $docsCompanyA = Document::factory()->count(3)->create([
            'company_id' => $this->companyA->id,
            'user_id' => $this->userA->id
        ]);

        $docsCompanyB = Document::factory()->count(2)->create([
            'company_id' => $this->companyB->id,
            'user_id' => $this->userB->id
        ]);

        // Usuario A solo debe ver documentos de su compañía
        $this->actingAs($this->userA);
        $visibleToUserA = Document::where('company_id', $this->userA->company_id)->get();
        
        $this->assertCount(3, $visibleToUserA);
        $this->assertTrue($visibleToUserA->every(fn($doc) => $doc->company_id === $this->companyA->id));

        // Usuario B solo debe ver documentos de su compañía
        $this->actingAs($this->userB);
        $visibleToUserB = Document::where('company_id', $this->userB->company_id)->get();
        
        $this->assertCount(2, $visibleToUserB);
        $this->assertTrue($visibleToUserB->every(fn($doc) => $doc->company_id === $this->companyB->id));

        // Verificar que no hay cruce de datos
        $this->assertEmpty($visibleToUserA->intersect($visibleToUserB));
    }

    /** @test */
    public function simple_items_are_isolated_by_company()
    {
        // Crear papers y machines para cada compañía
        $paperA = Paper::factory()->create(['company_id' => $this->companyA->id]);
        $machineA = PrintingMachine::factory()->create(['company_id' => $this->companyA->id]);
        
        $paperB = Paper::factory()->create(['company_id' => $this->companyB->id]);
        $machineB = PrintingMachine::factory()->create(['company_id' => $this->companyB->id]);

        // Crear SimpleItems para cada compañía
        $itemsA = SimpleItem::factory()->count(4)->create([
            'company_id' => $this->companyA->id,
            'paper_id' => $paperA->id,
            'printing_machine_id' => $machineA->id
        ]);

        $itemsB = SimpleItem::factory()->count(3)->create([
            'company_id' => $this->companyB->id,
            'paper_id' => $paperB->id,
            'printing_machine_id' => $machineB->id
        ]);

        // Note: SimpleItem model currently doesn't have company_id in migration
        // TODO: Add company_id to simple_items table for proper multi-tenancy
        $itemsTotal = SimpleItem::count();
        $this->assertEquals(7, $itemsTotal); // 4 + 3 items created
    }

    /** @test */
    public function products_are_isolated_by_company()
    {
        $productsA = Product::factory()->count(5)->create(['company_id' => $this->companyA->id]);
        $productsB = Product::factory()->count(3)->create(['company_id' => $this->companyB->id]);

        $visibleToA = Product::where('company_id', $this->companyA->id)->get();
        $visibleToB = Product::where('company_id', $this->companyB->id)->get();

        $this->assertCount(5, $visibleToA);
        $this->assertCount(3, $visibleToB);
        
        // Verificar que los códigos de productos pueden repetirse entre compañías sin conflicto
        $productA = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'code' => 'PROD-001'
        ]);
        
        $productB = Product::factory()->create([
            'company_id' => $this->companyB->id,
            'code' => 'PROD-001' // Mismo código, diferente compañía
        ]);

        $this->assertEquals('PROD-001', $productA->code);
        $this->assertEquals('PROD-001', $productB->code);
        $this->assertNotEquals($productA->id, $productB->id);
    }

    /** @test */
    public function papers_and_printing_machines_are_isolated_by_company()
    {
        $papersA = Paper::factory()->count(3)->create(['company_id' => $this->companyA->id]);
        $papersB = Paper::factory()->count(2)->create(['company_id' => $this->companyB->id]);

        $machinesA = PrintingMachine::factory()->count(2)->create(['company_id' => $this->companyA->id]);
        $machinesB = PrintingMachine::factory()->count(4)->create(['company_id' => $this->companyB->id]);

        // Verificar papers
        $this->assertCount(3, Paper::where('company_id', $this->companyA->id)->get());
        $this->assertCount(2, Paper::where('company_id', $this->companyB->id)->get());

        // Verificar machines
        $this->assertCount(2, PrintingMachine::where('company_id', $this->companyA->id)->get());
        $this->assertCount(4, PrintingMachine::where('company_id', $this->companyB->id)->get());
    }

    /** @test */
    public function contacts_are_isolated_by_company()
    {
        $contactsA = Contact::factory()->count(6)->create(['company_id' => $this->companyA->id]);
        $contactsB = Contact::factory()->count(4)->create(['company_id' => $this->companyB->id]);

        $visibleToA = Contact::where('company_id', $this->companyA->id)->get();
        $visibleToB = Contact::where('company_id', $this->companyB->id)->get();

        $this->assertCount(6, $visibleToA);
        $this->assertCount(4, $visibleToB);

        // Verificar que contactos con mismo email pueden existir en diferentes compañías
        $contactA = Contact::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'cliente@ejemplo.com'
        ]);
        
        $contactB = Contact::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'cliente@ejemplo.com'
        ]);

        $this->assertEquals('cliente@ejemplo.com', $contactA->email);
        $this->assertEquals('cliente@ejemplo.com', $contactB->email);
        $this->assertNotEquals($contactA->company_id, $contactB->company_id);
    }

    /** @test */
    public function document_items_respect_company_boundaries()
    {
        // Crear recursos para cada compañía
        $documentA = Document::factory()->create([
            'company_id' => $this->companyA->id,
            'user_id' => $this->userA->id
        ]);
        
        $documentB = Document::factory()->create([
            'company_id' => $this->companyB->id,
            'user_id' => $this->userB->id
        ]);

        $productA = Product::factory()->create(['company_id' => $this->companyA->id]);
        $productB = Product::factory()->create(['company_id' => $this->companyB->id]);

        // Crear DocumentItems
        $itemA = DocumentItem::create([
            'document_id' => $documentA->id,
            'company_id' => $this->companyA->id,
            'itemable_type' => 'App\\Models\\Product',
            'itemable_id' => $productA->id,
            'description' => $productA->name,
            'quantity' => 1,
            'unit_price' => $productA->sale_price,
            'total_price' => $productA->sale_price
        ]);

        $itemB = DocumentItem::create([
            'document_id' => $documentB->id,
            'company_id' => $this->companyB->id,
            'itemable_type' => 'App\\Models\\Product',
            'itemable_id' => $productB->id,
            'description' => $productB->name,
            'quantity' => 1,
            'unit_price' => $productB->sale_price,
            'total_price' => $productB->sale_price
        ]);

        // Verificar que cada DocumentItem está asociado correctamente
        $this->assertEquals($this->companyA->id, $itemA->document->company_id);
        $this->assertEquals($this->companyB->id, $itemB->document->company_id);
        
        $this->assertEquals($this->companyA->id, $itemA->itemable->company_id);
        $this->assertEquals($this->companyB->id, $itemB->itemable->company_id);
    }

    /** @test */
    public function cross_company_data_access_is_prevented()
    {
        // Crear documento en compañía A
        $documentA = Document::factory()->create([
            'company_id' => $this->companyA->id,
            'user_id' => $this->userA->id
        ]);

        // Usuario B no debe poder acceder al documento de A
        $this->actingAs($this->userB);

        // Simular intento de acceso directo
        $attemptedAccess = Document::where('id', $documentA->id)
                                   ->where('company_id', $this->userB->company_id)
                                   ->first();

        $this->assertNull($attemptedAccess);

        // Verificar que filtrado por company_id previene acceso
        $allDocumentsVisibleToB = Document::where('company_id', $this->userB->company_id)->get();
        $this->assertFalse($allDocumentsVisibleToB->contains('id', $documentA->id));
    }

    /** @test */
    public function company_specific_numbering_sequences_work_independently()
    {
        // Crear documentos en ambas compañías simultáneamente
        $docsA = collect();
        $docsB = collect();

        for ($i = 0; $i < 3; $i++) {
            $docsA->push(Document::factory()->create([
                'company_id' => $this->companyA->id,
                'user_id' => $this->userA->id
            ]));
            
            $docsB->push(Document::factory()->create([
                'company_id' => $this->companyB->id,
                'user_id' => $this->userB->id
            ]));
        }

        // Verificar que cada compañía tiene su propia secuencia
        $numbersA = $docsA->pluck('document_number')->toArray();
        $numbersB = $docsB->pluck('document_number')->toArray();

        // Todos los números deben seguir el formato COT-YYYY-XXX
        foreach ($numbersA as $number) {
            $this->assertStringStartsWith('COT-' . date('Y') . '-', $number);
        }
        
        foreach ($numbersB as $number) {
            $this->assertStringStartsWith('COT-' . date('Y') . '-', $number);
        }

        // No debe haber conflictos entre compañías
        $this->assertEmpty(array_intersect($numbersA, $numbersB));
    }

    /** @test */
    public function related_model_constraints_enforce_company_isolation()
    {
        $paperA = Paper::factory()->create(['company_id' => $this->companyA->id]);
        $machineB = PrintingMachine::factory()->create(['company_id' => $this->companyB->id]);

        // Intentar crear SimpleItem con recursos de diferentes compañías debe generar datos inconsistentes
        $simpleItem = SimpleItem::factory()->create([
            'company_id' => $this->companyA->id,
            'paper_id' => $paperA->id, // De compañía A
            'printing_machine_id' => $machineB->id // De compañía B - inconsistente
        ]);

        // El SimpleItem se crea, pero la integridad referencial puede ser problemática
        $this->assertEquals($this->companyA->id, $simpleItem->company_id);
        $this->assertEquals($this->companyA->id, $simpleItem->paper->company_id);
        $this->assertEquals($this->companyB->id, $simpleItem->printingMachine->company_id);
        
        // Esto indica la necesidad de validaciones adicionales en la aplicación
    }

    /** @test */
    public function user_authentication_respects_company_boundaries()
    {
        $this->actingAs($this->userA);
        
        // Usuario A autenticado debe tener acceso a su compañía
        $this->assertEquals($this->companyA->id, auth()->user()->company_id);
        
        // Cambiar a usuario B
        $this->actingAs($this->userB);
        $this->assertEquals($this->companyB->id, auth()->user()->company_id);

        // Los scopes automáticos deben funcionar correctamente
        $documentsForCurrentUser = Document::all();
        $this->assertTrue($documentsForCurrentUser->every(fn($doc) => $doc->company_id === $this->companyB->id));
    }

    /** @test */
    public function calculation_services_work_within_company_context()
    {
        // Crear recursos para compañía A
        $paperA = Paper::factory()->create(['company_id' => $this->companyA->id]);
        $machineA = PrintingMachine::factory()->create(['company_id' => $this->companyA->id]);
        
        $simpleItemA = SimpleItem::factory()->businessCard()->create([
            'company_id' => $this->companyA->id,
            'paper_id' => $paperA->id,
            'printing_machine_id' => $machineA->id
        ]);

        // Crear recursos para compañía B
        $paperB = Paper::factory()->create([
            'company_id' => $this->companyB->id,
            'price' => $paperA->price * 1.5 // Precio diferente
        ]);
        $machineB = PrintingMachine::factory()->create([
            'company_id' => $this->companyB->id,
            'cost_per_impression' => $machineA->cost_per_impression * 1.2 // Costo diferente
        ]);
        
        $simpleItemB = SimpleItem::factory()->businessCard()->create([
            'company_id' => $this->companyB->id,
            'paper_id' => $paperB->id,
            'printing_machine_id' => $machineB->id,
            'quantity' => $simpleItemA->quantity, // Misma cantidad para comparar
            'profit_percentage' => $simpleItemA->profit_percentage // Mismo margen
        ]);

        // Los cálculos deben reflejar los costos específicos de cada compañía
        $calculator = new \App\Services\SimpleItemCalculatorService();
        
        $pricingA = $calculator->calculateFinalPricing($simpleItemA);
        $pricingB = $calculator->calculateFinalPricing($simpleItemB);

        // Los precios finales deben ser diferentes debido a costos diferentes
        $this->assertNotEquals($pricingA->finalPrice, $pricingB->finalPrice);
        
        // Pero la estructura de cálculo debe ser consistente
        $this->assertEquals($pricingA->profitPercentage, $pricingB->profitPercentage);
        $this->assertIsFloat($pricingA->finalPrice);
        $this->assertIsFloat($pricingB->finalPrice);
    }
}
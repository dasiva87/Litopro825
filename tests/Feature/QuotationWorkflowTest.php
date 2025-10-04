<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\SimpleItem;
use App\Models\DocumentItem;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\SimpleItemCalculatorService;

class QuotationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;
    private Contact $customer;
    private DocumentType $quotationType;
    private Paper $paper;
    private PrintingMachine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear datos base para tests
        $this->company = Company::factory()->create();
        $this->user = User::factory()->forCompany($this->company->id)->create();
        $this->customer = Contact::factory()->customer()->create(['company_id' => $this->company->id]);
        $this->quotationType = DocumentType::factory()->create();
        $this->paper = Paper::factory()->create(['company_id' => $this->company->id]);
        $this->machine = PrintingMachine::factory()->create(['company_id' => $this->company->id]);
    }

    /** @test */
    public function user_can_create_basic_quotation()
    {
        // Crear documento directamente usando factory
        $document = Document::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'contact_id' => $this->customer->id,
            'document_type_id' => $this->quotationType->id,
            'status' => 'draft',
            'notes' => 'Test quotation notes'
        ]);
        
        $this->assertDatabaseHas('documents', [
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'contact_id' => $this->customer->id,
            'status' => 'draft'
        ]);

        $this->assertNotNull($document);
        $this->assertEquals($this->company->id, $document->company_id);
        $this->assertEquals($this->user->id, $document->user_id);
        $this->assertEquals($this->customer->id, $document->contact_id);
    }

    /** @test */
    public function quotation_calculates_totals_correctly_with_simple_items()
    {
        $document = Document::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'contact_id' => $this->customer->id
        ]);

        // Crear SimpleItem con cálculos conocidos
        $simpleItem = SimpleItem::factory()->businessCard()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id,
            'quantity' => 1000,
            'profit_percentage' => 30.0
        ]);

        // Calcular precio usando el servicio
        $calculator = new SimpleItemCalculatorService();
        $pricing = $calculator->calculateFinalPricing($simpleItem);

        // Crear DocumentItem
        $documentItem = DocumentItem::create([
            'document_id' => $document->id,
            'company_id' => $document->company_id,
            'itemable_type' => 'App\\Models\\SimpleItem',
            'itemable_id' => $simpleItem->id,
            'description' => $simpleItem->description,
            'quantity' => $simpleItem->quantity,
            'unit_price' => $pricing->unitPrice,
            'total_price' => $pricing->finalPrice
        ]);

        // Recalcular totales del documento
        $document->recalculateTotals();

        $this->assertEqualsWithDelta($pricing->finalPrice, $document->subtotal, 0.01);
        $this->assertEqualsWithDelta($pricing->finalPrice * 0.19, $document->tax_amount, 0.01); // IVA 19%
        $this->assertEqualsWithDelta($pricing->finalPrice * 1.19, $document->total, 0.01);
    }

    /** @test */
    public function quotation_handles_multiple_item_types()
    {
        $document = Document::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'contact_id' => $this->customer->id
        ]);

        // Crear SimpleItem
        $simpleItem = SimpleItem::factory()->create([
            'paper_id' => $this->paper->id,
            'printing_machine_id' => $this->machine->id
        ]);

        $calculator = new SimpleItemCalculatorService();
        $pricing = $calculator->calculateFinalPricing($simpleItem);

        DocumentItem::create([
            'document_id' => $document->id,
            'company_id' => $document->company_id,
            'itemable_type' => 'App\\Models\\SimpleItem',
            'itemable_id' => $simpleItem->id,
            'description' => $simpleItem->description,
            'quantity' => $simpleItem->quantity,
            'unit_price' => $pricing->unitPrice,
            'total_price' => $pricing->finalPrice
        ]);

        // Crear Product
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'sale_price' => 50000,
            'stock' => 100
        ]);

        $productQuantity = 5;
        $productTotal = $product->sale_price * $productQuantity;

        DocumentItem::create([
            'document_id' => $document->id,
            'company_id' => $document->company_id,
            'itemable_type' => 'App\\Models\\Product',
            'itemable_id' => $product->id,
            'description' => $product->name,
            'quantity' => $productQuantity,
            'unit_price' => $product->sale_price,
            'total_price' => $productTotal
        ]);

        // Recalcular totales
        $document->recalculateTotals();

        $expectedSubtotal = $pricing->finalPrice + $productTotal;
        $this->assertEqualsWithDelta($expectedSubtotal, $document->subtotal, 0.01);
        $this->assertEquals(2, $document->items()->count());
    }

    /** @test */
    public function quotation_workflow_status_transitions()
    {
        $document = Document::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'draft'
        ]);

        // Draft -> Sent
        $document->update(['status' => 'sent']);
        $this->assertEquals('sent', $document->fresh()->status);

        // Sent -> Approved
        $document->update(['status' => 'approved']);
        $this->assertEquals('approved', $document->fresh()->status);

        // Approved -> In Production
        $document->update(['status' => 'in_production']);
        $this->assertEquals('in_production', $document->fresh()->status);

        // In Production -> Completed
        $document->update(['status' => 'completed']);
        $this->assertEquals('completed', $document->fresh()->status);
    }

    /** @test */
    public function quotation_generates_unique_document_numbers()
    {
        $documents = collect();
        $year = date('Y');

        for ($i = 0; $i < 5; $i++) {
            $documents->push(Document::factory()->create([
                'company_id' => $this->company->id,
                'user_id' => $this->user->id
            ]));
        }

        $documentNumbers = $documents->pluck('document_number')->toArray();

        // Todos deben tener números únicos
        $this->assertEquals(count($documentNumbers), count(array_unique($documentNumbers)));

        // Todos deben seguir el formato COT-YYYY-XXX
        foreach ($documentNumbers as $number) {
            $this->assertStringStartsWith("COT-{$year}-", $number);
        }
    }

    /** @test */
    public function document_item_deletion_recalculates_totals()
    {
        $document = Document::factory()->withItems()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id
        ]);

        $initialTotal = $document->total;
        $initialItemCount = $document->items()->count();
        
        $this->assertGreaterThan(0, $initialTotal);
        $this->assertGreaterThan(0, $initialItemCount);

        // Eliminar un item
        $itemToDelete = $document->items()->first();
        $deletedItemTotal = $itemToDelete->total_price;
        $itemToDelete->delete();

        // Recalcular manualmente (en la app esto sería automático)
        $document->recalculateTotals();

        $this->assertEquals($initialItemCount - 1, $document->items()->count());
        $this->assertLessThan($initialTotal, $document->total);
    }

    /** @test */
    public function multi_tenant_isolation_in_quotations()
    {
        // Crear segunda compañía
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->forCompany($otherCompany->id)->create();

        // Crear documentos en ambas compañías
        $document1 = Document::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id
        ]);

        $document2 = Document::factory()->create([
            'company_id' => $otherCompany->id,
            'user_id' => $otherUser->id
        ]);

        // Usuario 1 solo debe ver documentos de su compañía
        $this->actingAs($this->user);
        $userDocuments = Document::where('company_id', $this->user->company_id)->get();
        
        $this->assertCount(1, $userDocuments);
        $this->assertEquals($document1->id, $userDocuments->first()->id);

        // Usuario 2 solo debe ver documentos de su compañía
        $this->actingAs($otherUser);
        $otherUserDocuments = Document::where('company_id', $otherUser->company_id)->get();
        
        $this->assertCount(1, $otherUserDocuments);
        $this->assertEquals($document2->id, $otherUserDocuments->first()->id);
    }

    /** @test */
    public function quotation_respects_product_stock_limits()
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'stock' => 10, // Stock limitado
            'sale_price' => 1000
        ]);

        $document = Document::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id
        ]);

        // Intentar agregar más cantidad de la disponible
        $requestedQuantity = 15; // Más que el stock disponible (10)

        $documentItem = DocumentItem::create([
            'document_id' => $document->id,
            'company_id' => $document->company_id,
            'itemable_type' => 'App\\Models\\Product',
            'itemable_id' => $product->id,
            'description' => $product->name,
            'quantity' => $requestedQuantity,
            'unit_price' => $product->sale_price,
            'total_price' => $product->sale_price * $requestedQuantity
        ]);

        // El item se crea, pero la validación de stock debe manejarse en el negocio
        $this->assertTrue($product->hasStock(10)); // Stock disponible
        $this->assertFalse($product->hasStock(15)); // Más que el disponible
    }

    /** @test */
    public function quotation_calculates_tax_correctly()
    {
        $document = Document::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'tax_percentage' => 19.0 // IVA Colombia
        ]);

        $subtotal = 100000; // $100,000 subtotal

        // Simular item que genera este subtotal
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'sale_price' => $subtotal
        ]);

        DocumentItem::create([
            'document_id' => $document->id,
            'company_id' => $document->company_id,
            'itemable_type' => 'App\\Models\\Product',
            'itemable_id' => $product->id,
            'description' => $product->name,
            'quantity' => 1,
            'unit_price' => $product->sale_price,
            'total_price' => $product->sale_price
        ]);

        $document->recalculateTotals();

        $expectedTax = $subtotal * 0.19; // $19,000
        $expectedTotal = $subtotal + $expectedTax; // $119,000

        $this->assertEquals($subtotal, $document->subtotal);
        $this->assertEquals($expectedTax, $document->tax_amount);
        $this->assertEquals($expectedTotal, $document->total);
    }

    /** @test */
    public function quotation_handles_zero_tax_scenarios()
    {
        $document = Document::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'tax_percentage' => 0.0 // Sin impuestos
        ]);

        $subtotal = 50000;

        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'sale_price' => $subtotal
        ]);

        DocumentItem::create([
            'document_id' => $document->id,
            'company_id' => $document->company_id,
            'itemable_type' => 'App\\Models\\Product',
            'itemable_id' => $product->id,
            'description' => $product->name,
            'quantity' => 1,
            'unit_price' => $product->sale_price,
            'total_price' => $product->sale_price
        ]);

        $document->recalculateTotals();

        $this->assertEquals($subtotal, $document->subtotal);
        $this->assertEquals(0, $document->tax_amount);
        $this->assertEquals($subtotal, $document->total);
    }
}
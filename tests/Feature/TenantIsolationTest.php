<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\SimpleItem;
use App\Models\Product;
use App\Models\DocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $companyAUser;
    protected User $companyBUser;
    protected Company $companyA;
    protected Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear dos empresas distintas
        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        // Crear usuarios para cada empresa
        $this->companyAUser = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'usera@companya.com',
        ]);

        $this->companyBUser = User::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'userb@companyb.com',
        ]);
    }

    /**
     * Helper para establecer el contexto de tenant en tests
     */
    protected function setTenantContext(User $user): void
    {
        $this->actingAs($user);
        config(['app.current_tenant_id' => $user->company_id]);
    }

    /** @test */
    public function contacts_are_isolated_by_tenant()
    {
        // Crear contactos para cada empresa
        $contactA = Contact::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'Contact A',
        ]);

        $contactB = Contact::factory()->create([
            'company_id' => $this->companyB->id,
            'name' => 'Contact B',
        ]);

        // Usuario A solo ve contactos de su empresa
        $this->setTenantContext($this->companyAUser);
        $contacts = Contact::all();

        $this->assertCount(1, $contacts);
        $this->assertEquals('Contact A', $contacts->first()->name);
        $this->assertFalse($contacts->contains('id', $contactB->id));

        // Usuario B solo ve contactos de su empresa
        $this->setTenantContext($this->companyBUser);
        $contacts = Contact::all();

        $this->assertCount(1, $contacts);
        $this->assertEquals('Contact B', $contacts->first()->name);
        $this->assertFalse($contacts->contains('id', $contactA->id));
    }

    /** @test */
    public function documents_are_isolated_by_tenant()
    {
        // Crear tipo de documento
        $documentType = DocumentType::factory()->create();

        // Crear documentos para cada empresa
        $documentA = Document::factory()->create([
            'company_id' => $this->companyA->id,
            'document_type_id' => $documentType->id,
            'document_number' => 'DOC-A-001',
        ]);

        $documentB = Document::factory()->create([
            'company_id' => $this->companyB->id,
            'document_type_id' => $documentType->id,
            'document_number' => 'DOC-B-001',
        ]);

        // Usuario A solo ve documentos de su empresa
        $this->setTenantContext($this->companyAUser);
        $documents = Document::all();

        $this->assertCount(1, $documents);
        $this->assertEquals('DOC-A-001', $documents->first()->document_number);
        $this->assertFalse($documents->contains('id', $documentB->id));

        // Usuario B solo ve documentos de su empresa
        $this->setTenantContext($this->companyBUser);
        $documents = Document::all();

        $this->assertCount(1, $documents);
        $this->assertEquals('DOC-B-001', $documents->first()->document_number);
        $this->assertFalse($documents->contains('id', $documentA->id));
    }

    /** @test */
    public function document_items_are_isolated_by_tenant()
    {
        // Crear documentos y items para cada empresa
        $documentType = DocumentType::factory()->create();

        $documentA = Document::factory()->create([
            'company_id' => $this->companyA->id,
            'document_type_id' => $documentType->id,
        ]);

        $documentB = Document::factory()->create([
            'company_id' => $this->companyB->id,
            'document_type_id' => $documentType->id,
        ]);

        // Crear simple items para cada documento
        $simpleItemA = SimpleItem::factory()->create([
            'company_id' => $this->companyA->id,
            'description' => 'Item A',
        ]);

        $simpleItemB = SimpleItem::factory()->create([
            'company_id' => $this->companyB->id,
            'description' => 'Item B',
        ]);

        $docItemA = DocumentItem::factory()->create([
            'document_id' => $documentA->id,
            'company_id' => $this->companyA->id,
            'itemable_type' => SimpleItem::class,
            'itemable_id' => $simpleItemA->id,
            'description' => 'Doc Item A',
        ]);

        $docItemB = DocumentItem::factory()->create([
            'document_id' => $documentB->id,
            'company_id' => $this->companyB->id,
            'itemable_type' => SimpleItem::class,
            'itemable_id' => $simpleItemB->id,
            'description' => 'Doc Item B',
        ]);

        // Usuario A solo ve document items de su empresa
        $this->setTenantContext($this->companyAUser);
        $items = DocumentItem::all();

        $this->assertCount(1, $items);
        $this->assertEquals('Doc Item A', $items->first()->description);
        $this->assertFalse($items->contains('id', $docItemB->id));

        // Usuario B solo ve document items de su empresa
        $this->setTenantContext($this->companyBUser);
        $items = DocumentItem::all();

        $this->assertCount(1, $items);
        $this->assertEquals('Doc Item B', $items->first()->description);
        $this->assertFalse($items->contains('id', $docItemA->id));
    }

    /** @test */
    public function products_are_isolated_by_tenant()
    {
        // Crear productos para cada empresa
        $productA = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'Product A',
        ]);

        $productB = Product::factory()->create([
            'company_id' => $this->companyB->id,
            'name' => 'Product B',
        ]);

        // Usuario A solo ve productos de su empresa
        $this->setTenantContext($this->companyAUser);
        $products = Product::all();

        $this->assertCount(1, $products);
        $this->assertEquals('Product A', $products->first()->name);
        $this->assertFalse($products->contains('id', $productB->id));

        // Usuario B solo ve productos de su empresa
        $this->setTenantContext($this->companyBUser);
        $products = Product::all();

        $this->assertCount(1, $products);
        $this->assertEquals('Product B', $products->first()->name);
        $this->assertFalse($products->contains('id', $productA->id));
    }

    /** @test */
    public function tenant_context_is_automatically_set_on_create()
    {
        // Usuario A crea un contacto sin especificar company_id
        $this->setTenantContext($this->companyAUser);

        $contact = Contact::create([
            'name' => 'Auto Contact',
            'type' => 'customer',
            'email' => 'auto@test.com',
        ]);

        // Verificar que company_id se asignó automáticamente
        $this->assertEquals($this->companyA->id, $contact->company_id);

        // Usuario B no puede ver este contacto
        $this->setTenantContext($this->companyBUser);
        $this->assertNull(Contact::find($contact->id));
    }

    /** @test */
    public function users_cannot_access_other_tenant_records_directly()
    {
        // Crear un contacto para empresa A
        $contactA = Contact::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'Private Contact',
        ]);

        // Usuario B intenta acceder directamente por ID
        $this->setTenantContext($this->companyBUser);

        $foundContact = Contact::find($contactA->id);

        // El scope debe bloquear el acceso
        $this->assertNull($foundContact);
    }

    /** @test */
    public function scope_can_be_disabled_when_needed()
    {
        // Crear contactos para ambas empresas
        Contact::factory()->create(['company_id' => $this->companyA->id]);
        Contact::factory()->create(['company_id' => $this->companyB->id]);

        $this->setTenantContext($this->companyAUser);

        // Con scope: solo ve 1
        $this->assertCount(1, Contact::all());

        // Sin scope: ve todos (2)
        $allContacts = Contact::withoutGlobalScopes()->get();
        $this->assertCount(2, $allContacts);
    }

    /** @test */
    public function related_models_respect_tenant_isolation()
    {
        $documentType = DocumentType::factory()->create();

        // Empresa A: documento con contacto
        $contactA = Contact::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $documentA = Document::factory()->create([
            'company_id' => $this->companyA->id,
            'contact_id' => $contactA->id,
            'document_type_id' => $documentType->id,
        ]);

        // Empresa B: documento con contacto
        $contactB = Contact::factory()->create([
            'company_id' => $this->companyB->id,
        ]);

        $documentB = Document::factory()->create([
            'company_id' => $this->companyB->id,
            'contact_id' => $contactB->id,
            'document_type_id' => $documentType->id,
        ]);

        // Usuario A: puede acceder a su documento y su contacto relacionado
        $this->setTenantContext($this->companyAUser);
        $document = Document::with('contact')->first();

        $this->assertNotNull($document);
        $this->assertEquals($contactA->id, $document->contact->id);

        // Usuario B: solo ve su documento y contacto
        $this->setTenantContext($this->companyBUser);
        $document = Document::with('contact')->first();

        $this->assertNotNull($document);
        $this->assertEquals($contactB->id, $document->contact->id);
        $this->assertNotEquals($contactA->id, $document->contact->id);
    }

    /** @test */
    public function queries_with_where_clauses_still_respect_tenant_scope()
    {
        // Crear contactos para ambas empresas con mismo email
        $contactA = Contact::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'shared@test.com',
            'name' => 'Contact A',
        ]);

        $contactB = Contact::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'shared@test.com',
            'name' => 'Contact B',
        ]);

        // Usuario A busca por email
        $this->setTenantContext($this->companyAUser);
        $found = Contact::where('email', 'shared@test.com')->first();

        $this->assertNotNull($found);
        $this->assertEquals('Contact A', $found->name);
        $this->assertEquals($this->companyA->id, $found->company_id);

        // Usuario B busca por mismo email
        $this->setTenantContext($this->companyBUser);
        $found = Contact::where('email', 'shared@test.com')->first();

        $this->assertNotNull($found);
        $this->assertEquals('Contact B', $found->name);
        $this->assertEquals($this->companyB->id, $found->company_id);
    }
}

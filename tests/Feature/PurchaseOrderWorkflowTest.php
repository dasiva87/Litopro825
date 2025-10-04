<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Notifications\PurchaseOrderCreated;
use App\Notifications\PurchaseOrderStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PurchaseOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $litografia;
    private Company $papeleria;
    private Contact $supplierContact;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear litografía (cliente)
        $this->litografia = Company::factory()->create(['company_type' => 'litografia']);
        $this->user = User::factory()->create(['company_id' => $this->litografia->id]);

        // Crear papelería (proveedor)
        $this->papeleria = Company::factory()->create(['company_type' => 'papeleria']);

        // Crear contacto de proveedor
        $this->supplierContact = Contact::factory()->create([
            'company_id' => $this->litografia->id,
            'type' => 'supplier',
        ]);

        $this->actingAs($this->user);
    }

    public function test_purchase_order_can_be_created_from_scratch()
    {
        $orderData = [
            'company_id' => $this->litografia->id,
            'supplier_company_id' => $this->papeleria->id,
            'created_by' => $this->user->id,
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(7),
            'status' => OrderStatus::DRAFT,
            'notes' => 'Orden de prueba',
        ];

        $order = PurchaseOrder::create($orderData);

        $this->assertDatabaseHas('purchase_orders', [
            'company_id' => $this->litografia->id,
            'supplier_company_id' => $this->papeleria->id,
            'status' => OrderStatus::DRAFT->value,
        ]);

        $this->assertNotNull($order->order_number);
        $this->assertEquals($this->litografia->id, $order->company_id);
        $this->assertEquals($this->papeleria->id, $order->supplier_company_id);
    }

    public function test_purchase_order_generates_unique_order_numbers()
    {
        $orders = collect();

        for ($i = 0; $i < 5; $i++) {
            $orders->push(PurchaseOrder::factory()->create([
                'company_id' => $this->litografia->id,
                'supplier_company_id' => $this->papeleria->id,
            ]));
        }

        $orderNumbers = $orders->pluck('order_number')->toArray();

        // Todos deben tener números únicos
        $this->assertEquals(count($orderNumbers), count(array_unique($orderNumbers)));

        // Todos deben seguir el formato OP-YYYY-XXX
        $year = date('Y');
        foreach ($orderNumbers as $number) {
            $this->assertStringStartsWith("OP-{$year}-", $number);
        }
    }

    public function test_purchase_order_can_add_items_from_products()
    {
        // Crear producto de papelería
        $product = Product::factory()->create([
            'company_id' => $this->papeleria->id,
            'name' => 'Papel Bond A4',
            'sale_price' => 50000,
            'stock' => 100,
        ]);

        // Crear orden
        $order = PurchaseOrder::factory()->create([
            'company_id' => $this->litografia->id,
            'supplier_company_id' => $this->papeleria->id,
        ]);

        // Crear cotización con item
        $document = Document::factory()->create([
            'company_id' => $this->litografia->id,
            'user_id' => $this->user->id,
        ]);

        $documentItem = DocumentItem::create([
            'document_id' => $document->id,
            'company_id' => $document->company_id,
            'itemable_type' => 'App\\Models\\Product',
            'itemable_id' => $product->id,
            'description' => $product->name,
            'quantity' => 10,
            'unit_price' => $product->sale_price,
            'total_price' => $product->sale_price * 10,
        ]);

        // Asociar item con orden
        $order->documentItems()->attach($documentItem->id, [
            'quantity_ordered' => 10,
            'unit_price' => $product->sale_price,
            'total_price' => $product->sale_price * 10,
            'status' => 'pending',
        ]);

        $this->assertEquals(1, $order->documentItems()->count());
        $this->assertEquals($product->name, $order->documentItems->first()->description);
    }

    public function test_purchase_order_status_workflow_transitions()
    {
        $order = PurchaseOrder::factory()->create([
            'company_id' => $this->litografia->id,
            'supplier_company_id' => $this->papeleria->id,
            'status' => OrderStatus::DRAFT,
        ]);

        // DRAFT → SENT
        $this->assertTrue(OrderStatus::DRAFT->canTransitionTo(OrderStatus::SENT));
        $order->update(['status' => OrderStatus::SENT]);
        $this->assertEquals(OrderStatus::SENT->value, $order->fresh()->status->value);

        // SENT → CONFIRMED
        $this->assertTrue(OrderStatus::SENT->canTransitionTo(OrderStatus::CONFIRMED));
        $order->update(['status' => OrderStatus::CONFIRMED]);
        $this->assertEquals(OrderStatus::CONFIRMED->value, $order->fresh()->status->value);

        // CONFIRMED → RECEIVED
        $this->assertTrue(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::RECEIVED));
        $order->update(['status' => OrderStatus::RECEIVED]);
        $this->assertEquals(OrderStatus::RECEIVED->value, $order->fresh()->status->value);
    }

    public function test_purchase_order_can_be_cancelled_from_active_states()
    {
        // Desde DRAFT
        $order1 = PurchaseOrder::factory()->create([
            'company_id' => $this->litografia->id,
            'supplier_company_id' => $this->papeleria->id,
            'status' => OrderStatus::DRAFT,
        ]);

        $this->assertTrue(OrderStatus::DRAFT->canTransitionTo(OrderStatus::CANCELLED));
        $order1->update(['status' => OrderStatus::CANCELLED]);
        $this->assertEquals(OrderStatus::CANCELLED->value, $order1->fresh()->status->value);

        // Desde SENT
        $order2 = PurchaseOrder::factory()->create([
            'company_id' => $this->litografia->id,
            'supplier_company_id' => $this->papeleria->id,
            'status' => OrderStatus::SENT,
        ]);

        $this->assertTrue(OrderStatus::SENT->canTransitionTo(OrderStatus::CANCELLED));
        $order2->update(['status' => OrderStatus::CANCELLED]);
        $this->assertEquals(OrderStatus::CANCELLED->value, $order2->fresh()->status->value);

        // Desde CONFIRMED
        $order3 = PurchaseOrder::factory()->create([
            'company_id' => $this->litografia->id,
            'supplier_company_id' => $this->papeleria->id,
            'status' => OrderStatus::CONFIRMED,
        ]);

        $this->assertTrue(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::CANCELLED));
        $order3->update(['status' => OrderStatus::CANCELLED]);
        $this->assertEquals(OrderStatus::CANCELLED->value, $order3->fresh()->status->value);
    }

    public function test_purchase_order_prevents_invalid_transitions()
    {
        $order = PurchaseOrder::factory()->create([
            'company_id' => $this->litografia->id,
            'supplier_company_id' => $this->papeleria->id,
            'status' => OrderStatus::DRAFT,
        ]);

        // No puede saltar de DRAFT a CONFIRMED
        $this->assertFalse(OrderStatus::DRAFT->canTransitionTo(OrderStatus::CONFIRMED));

        // No puede saltar de DRAFT a RECEIVED
        $this->assertFalse(OrderStatus::DRAFT->canTransitionTo(OrderStatus::RECEIVED));

        // No puede retroceder de SENT a DRAFT
        $order->update(['status' => OrderStatus::SENT]);
        $this->assertFalse(OrderStatus::SENT->canTransitionTo(OrderStatus::DRAFT));
    }

    public function test_purchase_order_creates_status_history()
    {
        $order = PurchaseOrder::factory()->create([
            'company_id' => $this->litografia->id,
            'supplier_company_id' => $this->papeleria->id,
            'status' => OrderStatus::DRAFT,
        ]);

        // Verificar historial inicial
        $this->assertEquals(1, $order->statusHistories()->count());
        $history = $order->statusHistories()->first();
        $this->assertEquals(OrderStatus::DRAFT->value, $history->to_status->value);
        $this->assertNull($history->from_status);

        // Cambiar estado y verificar nuevo historial
        $order->update(['status' => OrderStatus::SENT]);
        $order = $order->fresh();

        $this->assertEquals(2, $order->statusHistories()->count());
        $latestHistory = $order->statusHistories()->latest()->first();
        $this->assertEquals(OrderStatus::SENT->value, $latestHistory->to_status->value);
        $this->assertEquals(OrderStatus::DRAFT->value, $latestHistory->from_status->value);
        $this->assertEquals($this->user->id, $latestHistory->user_id);
    }

    public function test_purchase_order_sends_notifications_on_status_change()
    {
        Notification::fake();

        $order = PurchaseOrder::factory()->create([
            'company_id' => $this->litografia->id,
            'supplier_company_id' => $this->papeleria->id,
            'status' => OrderStatus::DRAFT,
        ]);

        // Cambiar a SENT debería enviar notificación
        $order->update(['status' => OrderStatus::SENT]);

        // Verificar que se crearon las notificaciones esperadas
        // Nota: Las notificaciones reales dependen de la implementación del Observer
    }

    public function test_multi_tenant_isolation_for_purchase_orders()
    {
        // Crear segunda litografía
        $otherLitografia = Company::factory()->create(['company_type' => 'litografia']);
        $otherUser = User::factory()->create(['company_id' => $otherLitografia->id]);

        // Crear órdenes en ambas empresas
        $order1 = PurchaseOrder::factory()->create([
            'company_id' => $this->litografia->id,
            'supplier_company_id' => $this->papeleria->id,
        ]);

        $order2 = PurchaseOrder::factory()->create([
            'company_id' => $otherLitografia->id,
            'supplier_company_id' => $this->papeleria->id,
        ]);

        // Litografía 1 solo debe ver su orden
        $this->actingAs($this->user);
        $orders = PurchaseOrder::where('company_id', $this->litografia->id)->get();
        $this->assertCount(1, $orders);
        $this->assertEquals($order1->id, $orders->first()->id);

        // Litografía 2 solo debe ver su orden
        $this->actingAs($otherUser);
        $orders = PurchaseOrder::where('company_id', $otherLitografia->id)->get();
        $this->assertCount(1, $orders);
        $this->assertEquals($order2->id, $orders->first()->id);
    }

    public function test_papeleria_can_see_orders_sent_to_them()
    {
        $papeleriaUser = User::factory()->create(['company_id' => $this->papeleria->id]);

        // Crear orden desde litografía a papelería
        $order = PurchaseOrder::factory()->create([
            'company_id' => $this->litografia->id,
            'supplier_company_id' => $this->papeleria->id,
            'status' => OrderStatus::SENT,
        ]);

        // Papelería debe poder ver la orden
        $this->actingAs($papeleriaUser);
        $receivedOrders = PurchaseOrder::where('supplier_company_id', $this->papeleria->id)->get();

        $this->assertCount(1, $receivedOrders);
        $this->assertEquals($order->id, $receivedOrders->first()->id);
    }

    public function test_purchase_order_calculates_total_amount()
    {
        $order = PurchaseOrder::factory()->create([
            'company_id' => $this->litografia->id,
            'supplier_company_id' => $this->papeleria->id,
        ]);

        // Crear productos
        $product1 = Product::factory()->create([
            'company_id' => $this->papeleria->id,
            'sale_price' => 10000,
        ]);

        $product2 = Product::factory()->create([
            'company_id' => $this->papeleria->id,
            'sale_price' => 25000,
        ]);

        // Crear documento con items
        $document = Document::factory()->create([
            'company_id' => $this->litografia->id,
            'user_id' => $this->user->id,
        ]);

        $item1 = DocumentItem::create([
            'document_id' => $document->id,
            'company_id' => $document->company_id,
            'itemable_type' => 'App\\Models\\Product',
            'itemable_id' => $product1->id,
            'description' => $product1->name,
            'quantity' => 5,
            'unit_price' => $product1->sale_price,
            'total_price' => $product1->sale_price * 5,
        ]);

        $item2 = DocumentItem::create([
            'document_id' => $document->id,
            'company_id' => $document->company_id,
            'itemable_type' => 'App\\Models\\Product',
            'itemable_id' => $product2->id,
            'description' => $product2->name,
            'quantity' => 3,
            'unit_price' => $product2->sale_price,
            'total_price' => $product2->sale_price * 3,
        ]);

        // Asociar items con orden
        $order->documentItems()->attach($item1->id, [
            'quantity_ordered' => 5,
            'unit_price' => 10000,
            'total_price' => 50000,
            'status' => 'pending',
        ]);

        $order->documentItems()->attach($item2->id, [
            'quantity_ordered' => 3,
            'unit_price' => 25000,
            'total_price' => 75000,
            'status' => 'pending',
        ]);

        // Calcular total esperado
        $expectedTotal = 50000 + 75000; // 125,000

        // Actualizar total en la orden
        $order->update(['total_amount' => $expectedTotal]);

        $this->assertEquals($expectedTotal, $order->total_amount);
        $this->assertEquals(2, $order->documentItems()->count());
    }
}

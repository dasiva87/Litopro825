<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use App\Models\Contact;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\SimpleItem;
use App\Models\DocumentItem;
use App\Models\Product;
use App\Services\SimpleItemCalculatorService;

class DemoQuotationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📋 Creando cotización de demostración...');

        $company = Company::where('slug', 'litopro-demo')->first();
        if (!$company) {
            $this->command->error('❌ Empresa de prueba no encontrada. Ejecuta TestDataSeeder primero.');
            return;
        }

        $user = $company->users->where('email', 'admin@litopro.test')->first();
        $customer = $company->contacts()->where('type', 'customer')->first();
        $quotationType = DocumentType::where('code', 'QUOTE')->first();

        if (!$user || !$customer || !$quotationType) {
            $this->command->error('❌ Faltan datos base. Ejecuta TestDataSeeder primero.');
            return;
        }

        // Crear cotización de demostración
        $documentNumber = 'COT-2025-DEMO-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $document = Document::firstOrCreate(
            [
                'company_id' => $company->id,
                'document_number' => 'COT-2025-DEMO-001'
            ],
            [
                'user_id' => $user->id,
                'contact_id' => $customer->id,
                'document_type_id' => $quotationType->id,
                'date' => now(),
                'due_date' => now()->addDays(30),
                'status' => 'draft',
                'notes' => 'Cotización de demostración del sistema LitoPro',
                'tax_percentage' => 19.0
            ]
        );

        // Agregar SimpleItems a la cotización
        $this->addSimpleItems($document, $company);
        
        // Agregar Products a la cotización
        $this->addProducts($document, $company);

        // Recalcular totales
        $document->recalculateTotals();

        $this->command->info("✅ Cotización de demostración creada: {$document->document_number}");
        $this->command->info("💰 Total: $" . number_format($document->total, 2));
        $this->command->info("📊 Items: {$document->items()->count()}");
        $this->command->info("👤 Cliente: {$customer->name}");
    }

    private function addSimpleItems(Document $document, Company $company): void
    {
        $calculator = new SimpleItemCalculatorService();
        $paper = $company->papers()->first();
        $machine = $company->printingMachines()->first();

        if (!$paper || !$machine) {
            $this->command->warn('⚠️ No hay papel o máquina disponible para SimpleItems');
            return;
        }

        // Item 1: Tarjetas de presentación
        $simpleItem1 = SimpleItem::create([
            'description' => 'Tarjetas de presentación ejecutivas',
            'quantity' => 1000,
            'horizontal_size' => 9.0,
            'vertical_size' => 5.5,
            'paper_id' => $paper->id,
            'printing_machine_id' => $machine->id,
            'ink_front_count' => 4,
            'ink_back_count' => 1,
            'front_back_plate' => false,
            'design_value' => 25000,
            'transport_value' => 8000,
            'rifle_value' => 0,
            'profit_percentage' => 35.0
        ]);

        $pricing1 = $calculator->calculateFinalPricing($simpleItem1);

        DocumentItem::create([
            'document_id' => $document->id,
            'itemable_type' => 'App\\Models\\SimpleItem',
            'itemable_id' => $simpleItem1->id,
            'description' => $simpleItem1->description,
            'quantity' => (float) $simpleItem1->quantity,
            'unit_price' => (float) $pricing1->unitPrice,
            'total_price' => (float) $pricing1->finalPrice,
        ]);

        // Item 2: Folletos promocionales
        $simpleItem2 = SimpleItem::create([
            'description' => 'Folletos promocionales A4',
            'quantity' => 2500,
            'horizontal_size' => 21.0,
            'vertical_size' => 29.7,
            'paper_id' => $paper->id,
            'printing_machine_id' => $machine->id,
            'ink_front_count' => 4,
            'ink_back_count' => 4,
            'front_back_plate' => false,
            'design_value' => 45000,
            'transport_value' => 15000,
            'rifle_value' => 12000,
            'profit_percentage' => 30.0
        ]);

        $pricing2 = $calculator->calculateFinalPricing($simpleItem2);

        DocumentItem::create([
            'document_id' => $document->id,
            'itemable_type' => 'App\\Models\\SimpleItem',
            'itemable_id' => $simpleItem2->id,
            'description' => $simpleItem2->description,
            'quantity' => (float) $simpleItem2->quantity,
            'unit_price' => (float) $pricing2->unitPrice,
            'total_price' => (float) $pricing2->finalPrice,
        ]);

        $this->command->info('   ✓ SimpleItems agregados');
    }

    private function addProducts(Document $document, Company $company): void
    {
        $products = $company->products()->where('active', true)->limit(2)->get();

        foreach ($products as $product) {
            $quantity = rand(10, 50);
            $unitPrice = $product->sale_price;
            $totalPrice = $quantity * $unitPrice;

            DocumentItem::create([
                'document_id' => $document->id,
                'itemable_type' => 'App\\Models\\Product',
                'itemable_id' => $product->id,
                'description' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ]);
        }

        $this->command->info('   ✓ Products agregados');
    }
}
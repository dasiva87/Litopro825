<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Paper;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Check if demo data already exists
        if (Document::where('document_number', 'like', 'COT-2025-%')->exists()) {
            $this->command->info('🎯 Demo data already exists! Skipping seeding.');
            $this->command->info('🚀 Dashboard ready! Access: /admin');
            $this->command->info('📧 Login: demo@litopro.test / password');
            return;
        }

        // Get the demo company or create it
        $company = Company::firstOrCreate([
            'name' => 'LitoPro Demo',
        ], [
            'email' => 'demo@litopro.com',
            'phone' => '+57 300 123 4567',
            'address' => 'Calle 123 #45-67',
            'city' => 'Cartagena',
            'state' => 'Bolívar',
            'country' => 'Colombia',
            'is_active' => true,
        ]);

        // Create demo user if not exists
        $user = User::firstOrCreate([
            'email' => 'demo@litopro.test',
        ], [
            'name' => 'Usuario Demo',
            'password' => Hash::make('password'),
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create document types
        $quotationType = DocumentType::firstOrCreate([
            'code' => 'QUOTE',
        ], [
            'name' => 'Cotización',
            'prefix' => 'COT',
            'is_active' => true,
        ]);

        $orderType = DocumentType::firstOrCreate([
            'code' => 'ORDER',
        ], [
            'name' => 'Orden de Trabajo',
            'prefix' => 'ORD',
            'is_active' => true,
        ]);

        $invoiceType = DocumentType::firstOrCreate([
            'code' => 'INVOICE',
        ], [
            'name' => 'Factura',
            'prefix' => 'FAC',
            'is_active' => true,
        ]);

        // Create some demo contacts
        $contacts = [];
        for ($i = 1; $i <= 5; $i++) {
            $contacts[] = Contact::create([
                'company_id' => $company->id,
                'name' => "Cliente Demo {$i}",
                'type' => 'customer',
                'email' => "cliente{$i}@demo.com",
                'phone' => "+57 300 123 456{$i}",
                'is_active' => true,
            ]);
        }

        // Create some demo papers
        $papers = [
            ['name' => 'Bond Blanco 75g', 'width' => 21.6, 'height' => 27.9, 'weight' => 75],
            ['name' => 'Couché 150g', 'width' => 21, 'height' => 29.7, 'weight' => 150],
            ['name' => 'Opalina 250g', 'width' => 21.6, 'height' => 27.9, 'weight' => 250],
        ];

        foreach ($papers as $i => $paperData) {
            Paper::firstOrCreate([
                'company_id' => $company->id,
                'code' => 'PAP-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
            ], [
                'name' => $paperData['name'],
                'width' => $paperData['width'],
                'height' => $paperData['height'],
                'weight' => $paperData['weight'],
                'cost_per_sheet' => rand(50, 200) / 100,
                'price' => rand(100, 400) / 100,
                'stock' => rand(100, 1000),
                'is_own' => true,
                'supplier_id' => null,
                'is_active' => true,
            ]);
        }

        // Create some demo products with varying stock levels
        $products = [
            ['name' => 'Tarjetas de Presentación', 'stock' => 2, 'min_stock' => 10], // Critical
            ['name' => 'Folletos A4', 'stock' => 8, 'min_stock' => 20], // Critical
            ['name' => 'Volantes Media Carta', 'stock' => 15, 'min_stock' => 25], // Low
            ['name' => 'Stickers Redondos', 'stock' => 50, 'min_stock' => 30], // OK
            ['name' => 'Carpetas Corporativas', 'stock' => 100, 'min_stock' => 50], // OK
        ];

        foreach ($products as $i => $productData) {
            Product::firstOrCreate([
                'company_id' => $company->id,
                'code' => 'PROD-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
            ], [
                'name' => $productData['name'],
                'description' => 'Producto de demostración para ' . $productData['name'],
                'purchase_price' => rand(1000, 5000),
                'sale_price' => rand(2000, 8000),
                'stock' => $productData['stock'],
                'min_stock' => $productData['min_stock'],
                'is_own_product' => true,
                'active' => true,
            ]);
        }

        // Create demo documents with different statuses and dates
        $statuses = ['draft', 'sent', 'approved', 'in_production', 'completed'];
        
        for ($i = 1; $i <= 15; $i++) {
            $createdAt = now()->subDays(rand(0, 30));
            $status = $statuses[array_rand($statuses)];
            
            $subtotal = rand(100000, 1000000);
            $taxAmount = $subtotal * 0.19;
            $total = $subtotal + $taxAmount;
            
            Document::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'contact_id' => $contacts[array_rand($contacts)]->id,
                'document_type_id' => $quotationType->id,
                'document_number' => 'COT-2025-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'date' => $createdAt->format('Y-m-d'),
                'due_date' => $createdAt->addDays(rand(7, 30))->format('Y-m-d'),
                'valid_until' => $createdAt->addDays(30)->format('Y-m-d'),
                'status' => $status,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'tax_percentage' => 19.00,
                'total' => $total,
                'notes' => 'Documento de demostración generado automáticamente.',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        // Create some production orders
        for ($i = 1; $i <= 8; $i++) {
            $createdAt = now()->subDays(rand(0, 15));
            
            $subtotal = rand(200000, 800000);
            $taxAmount = $subtotal * 0.19;
            $total = $subtotal + $taxAmount;
            
            Document::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'contact_id' => $contacts[array_rand($contacts)]->id,
                'document_type_id' => $orderType->id,
                'document_number' => 'ORD-2025-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'date' => $createdAt->format('Y-m-d'),
                'due_date' => $createdAt->addDays(rand(3, 15))->format('Y-m-d'),
                'status' => ['approved', 'in_production'][array_rand(['approved', 'in_production'])],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'tax_percentage' => 19.00,
                'total' => $total,
                'notes' => 'Orden de producción demo.',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $this->command->info('✅ Dashboard demo data created successfully!');
        $this->command->info('📊 Created:');
        $this->command->info("   • 1 Company: {$company->name}");
        $this->command->info("   • 1 User: {$user->email}");
        $this->command->info('   • 3 Document Types');
        $this->command->info('   • 5 Demo Contacts');
        $this->command->info('   • 3 Papers');
        $this->command->info('   • 5 Products (2 critical stock, 1 low stock)');
        $this->command->info('   • 15 Quotations (mixed statuses)');
        $this->command->info('   • 8 Production Orders');
        $this->command->info('');
        $this->command->info('🚀 Dashboard ready! Access: /admin');
        $this->command->info('📧 Login: demo@litopro.test / password');
    }
}
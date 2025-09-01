<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DigitalItem;
use App\Models\Company;
use App\Models\Contact;

class DigitalItemSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            // Obtener un proveedor de la empresa (si existe)
            $supplier = Contact::where('company_id', $company->id)
                ->where('is_supplier', true)
                ->first();

            // Items digitales por unidad
            DigitalItem::create([
                'company_id' => $company->id,
                'code' => 'DIG-001',
                'description' => 'Diseño de logotipo empresarial',
                'purchase_price' => 80000.00,
                'sale_price' => 150000.00,
                'pricing_type' => 'unit',
                'unit_value' => 150000.00,
                'is_own_product' => true,
                'active' => true,
            ]);

            DigitalItem::create([
                'company_id' => $company->id,
                'code' => 'DIG-002',
                'description' => 'Diseño de tarjeta de presentación',
                'purchase_price' => 25000.00,
                'sale_price' => 50000.00,
                'pricing_type' => 'unit',
                'unit_value' => 50000.00,
                'is_own_product' => true,
                'active' => true,
            ]);

            DigitalItem::create([
                'company_id' => $company->id,
                'code' => 'DIG-003',
                'description' => 'Diseño de volante publicitario',
                'purchase_price' => 35000.00,
                'sale_price' => 65000.00,
                'pricing_type' => 'unit',
                'unit_value' => 65000.00,
                'is_own_product' => true,
                'active' => true,
            ]);

            // Items digitales por tamaño (m²)
            DigitalItem::create([
                'company_id' => $company->id,
                'code' => 'DIG-004',
                'description' => 'Impresión digital en vinilo adhesivo',
                'purchase_price' => 18000.00,
                'sale_price' => 35000.00,
                'pricing_type' => 'size',
                'unit_value' => 35000.00, // Por m²
                'is_own_product' => false,
                'supplier_contact_id' => $supplier?->id,
                'active' => true,
            ]);

            DigitalItem::create([
                'company_id' => $company->id,
                'code' => 'DIG-005',
                'description' => 'Banner impreso en lona',
                'purchase_price' => 15000.00,
                'sale_price' => 28000.00,
                'pricing_type' => 'size',
                'unit_value' => 28000.00, // Por m²
                'is_own_product' => false,
                'supplier_contact_id' => $supplier?->id,
                'active' => true,
            ]);

            DigitalItem::create([
                'company_id' => $company->id,
                'code' => 'DIG-006',
                'description' => 'Impresión en canvas artístico',
                'purchase_price' => 25000.00,
                'sale_price' => 45000.00,
                'pricing_type' => 'size',
                'unit_value' => 45000.00, // Por m²
                'is_own_product' => false,
                'supplier_contact_id' => $supplier?->id,
                'active' => true,
            ]);

            // Item digital premium por unidad
            DigitalItem::create([
                'company_id' => $company->id,
                'code' => 'DIG-007',
                'description' => 'Identidad corporativa completa (logo + papelería)',
                'purchase_price' => 200000.00,
                'sale_price' => 450000.00,
                'pricing_type' => 'unit',
                'unit_value' => 450000.00,
                'is_own_product' => true,
                'active' => true,
                'metadata' => [
                    'incluye' => ['Logo', 'Tarjetas', 'Membrete', 'Sobre', 'Carpeta'],
                    'tiempo_entrega' => '5-7 días hábiles',
                    'revisiones' => 3
                ],
            ]);

            // Item digital por tamaño - formato grande
            DigitalItem::create([
                'company_id' => $company->id,
                'code' => 'DIG-008',
                'description' => 'Impresión gran formato en papel fotográfico',
                'purchase_price' => 22000.00,
                'sale_price' => 40000.00,
                'pricing_type' => 'size',
                'unit_value' => 40000.00, // Por m²
                'is_own_product' => false,
                'supplier_contact_id' => $supplier?->id,
                'active' => true,
                'metadata' => [
                    'resolucion' => '300 DPI',
                    'acabado' => 'Brillante/Mate',
                    'tiempo_produccion' => '24 horas'
                ],
            ]);
        }

        $this->command->info('✅ Items digitales creados exitosamente');
    }
}
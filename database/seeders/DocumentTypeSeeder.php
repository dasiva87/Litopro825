<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentType;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $documentTypes = [
            [
                'name' => 'Cotización',
                'code' => 'QUOTE',
                'description' => 'Documento con precios detallados de los productos solicitados por un cliente',
                'is_active' => true,
            ],
            [
                'name' => 'Orden de Producción', 
                'code' => 'ORDER',
                'description' => 'Instrucción detallada para operarios con datos de producción del producto cotizado',
                'is_active' => true,
            ],
            [
                'name' => 'Pedido de Papel',
                'code' => 'PAPER', 
                'description' => 'Solicitud que se envía a una papelería con la cantidad y tipo de papel necesarios',
                'is_active' => true,
            ],
            [
                'name' => 'Factura',
                'code' => 'INVOICE',
                'description' => 'Documento de cobro por productos o servicios prestados',
                'is_active' => true,
            ],
            [
                'name' => 'Orden de Compra',
                'code' => 'PURCHASE',
                'description' => 'Documento para solicitar productos o servicios a proveedores',
                'is_active' => true,
            ],
            [
                'name' => 'Remisión',
                'code' => 'DELIVERY',
                'description' => 'Documento de entrega de productos sin valor comercial',
                'is_active' => true,
            ],
        ];

        foreach ($documentTypes as $type) {
            DocumentType::firstOrCreate(
                ['code' => $type['code']],  // Buscar por código
                $type  // Si no existe, crear con estos datos
            );
        }

        $this->command->info('Document types seeded successfully!');
    }
}
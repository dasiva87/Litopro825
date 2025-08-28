<?php

namespace Database\Factories;

use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentType>
 */
class DocumentTypeFactory extends Factory
{
    protected $model = DocumentType::class;

    public function definition(): array
    {
        $types = [
            ['name' => 'Cotización', 'code' => 'QUOTE', 'description' => 'Documento de cotización para clientes'],
            ['name' => 'Factura', 'code' => 'INVOICE', 'description' => 'Documento de facturación'],
            ['name' => 'Orden de Trabajo', 'code' => 'WORK_ORDER', 'description' => 'Orden de trabajo para producción'],
            ['name' => 'Nota de Crédito', 'code' => 'CREDIT_NOTE', 'description' => 'Nota de crédito'],
            ['name' => 'Presupuesto', 'code' => 'BUDGET', 'description' => 'Presupuesto preliminar'],
        ];
        
        $selectedType = $this->faker->randomElement($types);
        
        return [
            'name' => $selectedType['name'],
            'code' => $selectedType['code'] . '-' . $this->faker->unique()->numberBetween(1, 9999),
            'description' => $selectedType['description'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function invoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Factura',
            'code' => 'INVOICE-' . $this->faker->unique()->numberBetween(1, 9999),
            'description' => 'Documento de facturación',
        ]);
    }

    public function order(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Orden de Trabajo',
            'code' => 'WORK_ORDER-' . $this->faker->unique()->numberBetween(1, 9999),
            'description' => 'Orden de trabajo para producción',
        ]);
    }
}
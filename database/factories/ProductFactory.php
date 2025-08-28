<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $productNames = [
            'Papel Bond 75g',
            'Cartulina Opalina 180g',
            'Propalcote 115g',
            'Tinta Negra Offset',
            'Tinta Cyan',
            'Placas de Impresión',
            'Blanqueta Offset',
            'Solvente de Limpieza',
        ];

        $purchasePrice = $this->faker->randomFloat(2, 1000, 50000);
        $salePrice = $purchasePrice * $this->faker->randomFloat(2, 1.2, 2.5); // Margen 20%-150%

        return [
            'company_id' => Company::factory(),
            'code' => strtoupper($this->faker->bothify('PRD-###-??')),
            'name' => $this->faker->randomElement($productNames),
            'description' => $this->faker->optional(0.8)->sentence(),
            'purchase_price' => $purchasePrice,
            'sale_price' => $salePrice,
            'stock' => $this->faker->numberBetween(0, 500),
            'min_stock' => $this->faker->numberBetween(5, 50),
            'is_own_product' => $this->faker->boolean(60), // 60% productos propios
            'supplier_contact_id' => null, // Se asigna condicionalmente
            'active' => true,
            'metadata' => $this->faker->optional(0.3)->passthrough([
                'category' => $this->faker->randomElement(['Papel', 'Tintas', 'Químicos', 'Repuestos']),
                'brand' => $this->faker->company(),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Product $product) {
            // Si no es producto propio, asignar proveedor
            if (!$product->is_own_product) {
                $product->supplier_contact_id = Contact::factory(['company_id' => $product->company_id])
                    ->supplier()
                    ->create()
                    ->id;
            }
        });
    }

    public function paper(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement(['Bond 75g', 'Opalina 180g', 'Propalcote 115g']),
            'metadata' => ['category' => 'Papel', 'grammage' => $this->faker->randomElement([75, 90, 115, 150, 180])],
        ]);
    }

    public function ink(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement(['Tinta Negra', 'Tinta Cyan', 'Tinta Magenta', 'Tinta Amarilla']),
            'metadata' => ['category' => 'Tintas', 'color' => $this->faker->colorName()],
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $this->faker->numberBetween(0, 5),
            'min_stock' => $this->faker->numberBetween(10, 20),
        ]);
    }

    public function ownProduct(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_own_product' => true,
            'supplier_contact_id' => null,
        ]);
    }

    public function supplierProduct(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_own_product' => false,
            // supplier_contact_id se asigna en configure()
        ]);
    }
}
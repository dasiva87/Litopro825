<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use App\Models\DigitalItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DigitalItem>
 */
class DigitalItemFactory extends Factory
{
    protected $model = DigitalItem::class;

    public function definition(): array
    {
        $descriptions = [
            'Impresión Gran Formato',
            'Vinilo Adhesivo',
            'Banner Publicitario',
            'Pasacalle',
            'Impresión en Lona',
            'Gigantografía',
            'Ploteo Vehicular',
        ];

        $pricingType = $this->faker->randomElement(['unit', 'size']);
        $purchasePrice = $this->faker->randomFloat(2, 5000, 50000);
        $profitMargin = $this->faker->randomFloat(2, 20, 60); // 20-60% margen
        $salePrice = $purchasePrice * (1 + ($profitMargin / 100));

        return [
            'company_id' => Company::factory(),
            'code' => null, // Se genera automáticamente en boot()
            'description' => $this->faker->randomElement($descriptions),
            'purchase_price' => $purchasePrice,
            'sale_price' => round($salePrice, 2),
            'profit_margin' => $profitMargin,
            'is_own_product' => $this->faker->boolean(70), // 70% productos propios
            'supplier_contact_id' => null, // Se asigna si is_own_product = false
            'pricing_type' => $pricingType,
            'unit_value' => $pricingType === 'unit'
                ? $this->faker->randomFloat(2, 1000, 20000)
                : $this->faker->randomFloat(2, 5000, 50000), // Por m² si es 'size'
            'metadata' => null,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Digital item con pricing por unidad
     */
    public function unitPricing(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => 'unit',
            'unit_value' => $this->faker->randomFloat(2, 1000, 15000),
            'description' => 'Impresión Digital Unitaria',
        ]);
    }

    /**
     * Digital item con pricing por tamaño (área)
     */
    public function sizePricing(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => 'size',
            'unit_value' => $this->faker->randomFloat(2, 10000, 80000), // Por m²
            'description' => 'Impresión Gran Formato',
        ]);
    }

    /**
     * Digital item de terceros (con proveedor)
     */
    public function fromSupplier(): static
    {
        return $this->state(function (array $attributes) {
            $company = Company::factory()->create();
            $supplier = Contact::factory()->create([
                'company_id' => $company->id,
                'type' => 'supplier',
            ]);

            return [
                'company_id' => $company->id,
                'is_own_product' => false,
                'supplier_contact_id' => $supplier->id,
            ];
        });
    }

    /**
     * Digital item propio (sin proveedor)
     */
    public function ownProduct(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_own_product' => true,
            'supplier_contact_id' => null,
        ]);
    }

    /**
     * Digital item activo
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Digital item inactivo
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Digital item con margen alto
     */
    public function highMargin(): static
    {
        return $this->state(function (array $attributes) {
            $purchasePrice = $this->faker->randomFloat(2, 10000, 30000);
            $profitMargin = $this->faker->randomFloat(2, 50, 80);
            $salePrice = $purchasePrice * (1 + ($profitMargin / 100));

            return [
                'purchase_price' => $purchasePrice,
                'profit_margin' => $profitMargin,
                'sale_price' => round($salePrice, 2),
            ];
        });
    }
}

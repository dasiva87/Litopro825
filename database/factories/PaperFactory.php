<?php

namespace Database\Factories;

use App\Models\Paper;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Paper>
 */
class PaperFactory extends Factory
{
    protected $model = Paper::class;

    public function definition(): array
    {
        $paperTypes = ['Bond', 'Propalcote', 'Opalina', 'Cartulina', 'Couché'];
        $weights = [75, 90, 115, 150, 180, 250, 300];
        
        return [
            'company_id' => Company::factory(),
            'code' => strtoupper($this->faker->bothify('PPR-###')), // Código del papel
            'name' => $this->faker->randomElement($paperTypes),
            'weight' => $this->faker->randomElement($weights),
            'width' => $this->faker->randomFloat(2, 50, 125), // Entre 50-125cm
            'height' => $this->faker->randomFloat(2, 50, 125),
            'cost_per_sheet' => $this->faker->randomFloat(4, 500, 5000), // Costo por pliego
            'price' => $this->faker->randomFloat(4, 600, 6000), // Precio venta (mayor que costo)
            'stock' => $this->faker->numberBetween(0, 1000), // Stock disponible
            'is_own' => $this->faker->boolean(80), // 80% papeles propios
            'supplier_id' => null, // Se asignará condicionalmente
            'is_active' => true,
        ];
    }


    public function bond(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'BOND-75',
            'name' => 'Bond',
            'weight' => 75,
        ]);
    }

    public function propalcote(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'PROP-115',
            'name' => 'Propalcote',
            'weight' => 115,
        ]);
    }
}
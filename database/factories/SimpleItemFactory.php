<?php

namespace Database\Factories;

use App\Models\SimpleItem;
use App\Models\Company;
use App\Models\Paper;
use App\Models\PrintingMachine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SimpleItem>
 */
class SimpleItemFactory extends Factory
{
    protected $model = SimpleItem::class;

    public function definition(): array
    {
        $descriptions = [
            'Tarjetas de presentación',
            'Folletos promocionales',
            'Volantes publicitarios',
            'Tarjetas personales',
            'Flyers informativos',
            'Material promocional',
        ];

        return [
            'description' => $this->faker->randomElement($descriptions),
            'quantity' => $this->faker->numberBetween(100, 10000),
            'sobrante_papel' => $this->faker->numberBetween(0, 200), // 0-200 unidades de sobrante
            'horizontal_size' => $this->faker->randomFloat(1, 5, 50), // 5-50cm
            'vertical_size' => $this->faker->randomFloat(1, 5, 50),
            'paper_id' => Paper::factory(),
            'printing_machine_id' => PrintingMachine::factory(),
            'ink_front_count' => $this->faker->numberBetween(1, 4),
            'ink_back_count' => $this->faker->numberBetween(0, 4),
            'front_back_plate' => $this->faker->boolean(30), // 30% probabilidad
            'design_value' => $this->faker->randomFloat(2, 0, 50000), // Puede ser 0 pero no null
            'transport_value' => $this->faker->randomFloat(2, 0, 20000), // Puede ser 0 pero no null
            'rifle_value' => $this->faker->randomFloat(2, 0, 10000), // No opcional, siempre un valor
            'cutting_cost' => $this->faker->randomFloat(2, 0, 15000), // Costo de corte manual
            'mounting_cost' => $this->faker->randomFloat(2, 0, 12000), // Costo de montaje manual
            'profit_percentage' => $this->faker->randomFloat(2, 15, 50), // 15-50% margen
            // Los campos calculados se llenan automáticamente por el modelo
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function businessCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => 'Tarjetas de presentación',
            'quantity' => $this->faker->numberBetween(500, 5000),
            'horizontal_size' => 9.0,
            'vertical_size' => 5.0,
            'ink_front_count' => 4,
            'ink_back_count' => 1,
        ]);
    }

    public function flyer(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => 'Volantes publicitarios',
            'quantity' => $this->faker->numberBetween(1000, 20000),
            'horizontal_size' => 21.0,
            'vertical_size' => 14.8, // A5
            'ink_front_count' => 4,
            'ink_back_count' => 4,
        ]);
    }

    public function brochure(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => 'Folletos promocionales',
            'quantity' => $this->faker->numberBetween(500, 5000),
            'horizontal_size' => 21.0,
            'vertical_size' => 29.7, // A4
            'ink_front_count' => 4,
            'ink_back_count' => 4,
        ]);
    }

    public function withoutAdditionalCosts(): static
    {
        return $this->state(fn (array $attributes) => [
            'design_value' => 0,
            'transport_value' => 0,
            'rifle_value' => 0,
            'cutting_cost' => 0,
            'mounting_cost' => 0,
        ]);
    }

    public function withAllCosts(): static
    {
        return $this->state(fn (array $attributes) => [
            'design_value' => $this->faker->randomFloat(2, 20000, 50000),
            'transport_value' => $this->faker->randomFloat(2, 10000, 20000),
            'rifle_value' => $this->faker->randomFloat(2, 5000, 10000),
            'cutting_cost' => $this->faker->randomFloat(2, 8000, 15000),
            'mounting_cost' => $this->faker->randomFloat(2, 5000, 12000),
        ]);
    }
}
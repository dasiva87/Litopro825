<?php

namespace Database\Factories;

use App\Models\MagazineItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class MagazineItemFactory extends Factory
{
    protected $model = MagazineItem::class;

    public function definition(): array
    {
        return [
            'description' => $this->faker->sentence(),
            'quantity' => $this->faker->numberBetween(10, 1000),
            'closed_width' => $this->faker->randomFloat(1, 10, 30),
            'closed_height' => $this->faker->randomFloat(1, 15, 40),
            'binding_type' => $this->faker->randomElement([
                'grapado', 'plegado', 'anillado', 'cosido', 
                'caballete', 'lomo', 'espiral', 'wire_o', 'hotmelt'
            ]),
            'binding_side' => $this->faker->randomElement(['arriba', 'izquierda', 'derecha', 'abajo']),
            'binding_cost' => 0,
            'assembly_cost' => 0,
            'finishing_cost' => 0,
            'transport_value' => $this->faker->numberBetween(0, 50000),
            'design_value' => $this->faker->numberBetween(0, 100000),
            'profit_percentage' => $this->faker->numberBetween(15, 50),
            'pages_total_cost' => 0,
            'total_cost' => 0,
            'final_price' => 0,
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    /**
     * Magazine with specific binding type
     */
    public function withBinding(string $bindingType, string $bindingSide = 'izquierda'): static
    {
        return $this->state(fn (array $attributes) => [
            'binding_type' => $bindingType,
            'binding_side' => $bindingSide,
        ]);
    }

    /**
     * Small magazine
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->numberBetween(10, 100),
            'closed_width' => $this->faker->randomFloat(1, 10, 15),
            'closed_height' => $this->faker->randomFloat(1, 15, 20),
        ]);
    }

    /**
     * Large magazine
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->numberBetween(500, 5000),
            'closed_width' => $this->faker->randomFloat(1, 25, 35),
            'closed_height' => $this->faker->randomFloat(1, 35, 50),
        ]);
    }

    /**
     * Magazine with high profit margin
     */
    public function highProfit(): static
    {
        return $this->state(fn (array $attributes) => [
            'profit_percentage' => $this->faker->numberBetween(40, 80),
        ]);
    }
}
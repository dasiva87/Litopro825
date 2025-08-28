<?php

namespace Database\Factories;

use App\Models\PrintingMachine;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PrintingMachine>
 */
class PrintingMachineFactory extends Factory
{
    protected $model = PrintingMachine::class;

    public function definition(): array
    {
        $machineTypes = ['offset', 'digital', 'gran_formato'];
        $brands = ['Heidelberg', 'Komori', 'Manroland', 'KBA', 'Xerox', 'Canon'];
        
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->randomElement($brands) . ' ' . ucfirst($this->faker->randomElement($machineTypes)),
            'type' => $this->faker->randomElement($machineTypes),
            'max_width' => $this->faker->randomFloat(2, 70, 125),
            'max_height' => $this->faker->randomFloat(2, 50, 125),
            'max_colors' => $this->faker->randomElement([1, 2, 4, 6, 8]),
            'cost_per_impression' => $this->faker->randomFloat(4, 50, 500), // $50-500 por impresión
            'setup_cost' => $this->faker->randomFloat(2, 5000, 25000), // $5k-25k alistamiento
            'is_own' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function offset(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'offset',
            'max_colors' => $this->faker->randomElement([4, 6, 8]),
            'cost_per_impression' => $this->faker->randomFloat(4, 200, 400),
        ]);
    }

    public function digital(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'digital',
            'max_colors' => 4,
            'cost_per_impression' => $this->faker->randomFloat(4, 300, 500),
            'setup_cost' => 0, // Digital no requiere alistamiento
        ]);
    }

    public function highCapacity(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_width' => 125,
            'max_height' => 125,
            'max_colors' => 8,
        ]);
    }
}
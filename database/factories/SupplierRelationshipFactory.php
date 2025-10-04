<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SupplierRelationship;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupplierRelationship>
 */
class SupplierRelationshipFactory extends Factory
{
    protected $model = SupplierRelationship::class;

    public function definition(): array
    {
        return [
            'client_company_id' => Company::factory(),
            'supplier_company_id' => Company::factory(),
            'approved_by_user_id' => User::factory(),
            'approved_at' => now(),
            'is_active' => true,
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Relación aprobada
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Relación pendiente de aprobación
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_at' => null,
            'approved_by_user_id' => null,
            'is_active' => false,
        ]);
    }

    /**
     * Relación inactiva
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Relación activa
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}

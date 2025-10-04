<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_company_id' => Company::factory(),
            'order_number' => null, // Se genera automáticamente
            'status' => OrderStatus::DRAFT,
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays($this->faker->numberBetween(7, 30)),
            'actual_delivery_date' => null,
            'total_amount' => $this->faker->randomFloat(2, 50000, 500000),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'created_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    /**
     * Orden en estado borrador
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::DRAFT,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Orden enviada
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::SENT,
        ]);
    }

    /**
     * Orden confirmada
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CONFIRMED,
        ]);
    }

    /**
     * Orden recibida
     */
    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::RECEIVED,
            'actual_delivery_date' => now(),
        ]);
    }

    /**
     * Orden cancelada
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CANCELLED,
        ]);
    }

    /**
     * Orden aprobada
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Orden con entrega vencida (overdue)
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'expected_delivery_date' => now()->subDays($this->faker->numberBetween(1, 10)),
            'status' => OrderStatus::SENT,
        ]);
    }

    /**
     * Orden con entrega próxima (upcoming)
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'expected_delivery_date' => now()->addDays($this->faker->numberBetween(1, 3)),
            'status' => OrderStatus::SENT,
        ]);
    }
}

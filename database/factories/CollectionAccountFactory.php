<?php

namespace Database\Factories;

use App\Enums\CollectionAccountStatus;
use App\Models\CollectionAccount;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CollectionAccount>
 */
class CollectionAccountFactory extends Factory
{
    protected $model = CollectionAccount::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'client_company_id' => Company::factory(),
            'account_number' => null, // Se genera automáticamente
            'status' => CollectionAccountStatus::DRAFT,
            'issue_date' => now(),
            'due_date' => now()->addDays($this->faker->numberBetween(15, 60)),
            'paid_date' => null,
            'total_amount' => $this->faker->randomFloat(2, 100000, 5000000),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'created_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    /**
     * Cuenta de cobro en estado borrador
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CollectionAccountStatus::DRAFT,
            'approved_by' => null,
            'approved_at' => null,
            'paid_date' => null,
        ]);
    }

    /**
     * Cuenta de cobro enviada al cliente
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CollectionAccountStatus::SENT,
        ]);
    }

    /**
     * Cuenta de cobro aprobada por el cliente
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CollectionAccountStatus::APPROVED,
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Cuenta de cobro pagada
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CollectionAccountStatus::PAID,
            'approved_by' => User::factory(),
            'approved_at' => now()->subDays($this->faker->numberBetween(5, 15)),
            'paid_date' => now(),
        ]);
    }

    /**
     * Cuenta de cobro cancelada
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CollectionAccountStatus::CANCELLED,
        ]);
    }

    /**
     * Cuenta de cobro vencida (overdue)
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->subDays($this->faker->numberBetween(1, 30)),
            'status' => CollectionAccountStatus::SENT,
        ]);
    }

    /**
     * Cuenta de cobro próxima a vencer
     */
    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->addDays($this->faker->numberBetween(1, 5)),
            'status' => CollectionAccountStatus::SENT,
        ]);
    }

    /**
     * Cuenta de cobro con monto específico
     */
    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'total_amount' => $amount,
        ]);
    }
}

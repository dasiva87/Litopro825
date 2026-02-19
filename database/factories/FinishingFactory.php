<?php

namespace Database\Factories;

use App\Enums\FinishingMeasurementUnit;
use App\Models\Company;
use App\Models\Finishing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Finishing>
 */
class FinishingFactory extends Factory
{
    protected $model = Finishing::class;

    public function definition(): array
    {
        $finishingNames = [
            'Laminado Brillante',
            'Laminado Mate',
            'Barniz UV',
            'Troquel',
            'Perforado',
            'Encuadernación',
            'Cosido',
            'Pegado',
            'Doblado',
            'Empastado',
        ];

        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->randomElement($finishingNames),
            'description' => $this->faker->optional(0.7)->sentence(),
            'unit_price' => $this->faker->randomFloat(2, 5000, 50000),
            'measurement_unit' => $this->faker->randomElement(FinishingMeasurementUnit::cases()),
            'is_own_provider' => $this->faker->boolean(70), // 70% propios
            'active' => true,
        ];
    }

    /**
     * Finishing activo
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Finishing inactivo
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Finishing por unidad
     */
    public function perUnit(): static
    {
        return $this->state(fn (array $attributes) => [
            'measurement_unit' => FinishingMeasurementUnit::UNIDAD,
            'unit_price' => $this->faker->randomFloat(2, 1000, 10000),
        ]);
    }

    /**
     * Finishing por millar
     */
    public function perThousand(): static
    {
        return $this->state(fn (array $attributes) => [
            'measurement_unit' => FinishingMeasurementUnit::MILLAR,
            'unit_price' => $this->faker->randomFloat(2, 10000, 50000),
        ]);
    }

    /**
     * Finishing por tamaño
     */
    public function perSize(): static
    {
        return $this->state(fn (array $attributes) => [
            'measurement_unit' => FinishingMeasurementUnit::TAMAÑO,
            'unit_price' => $this->faker->randomFloat(2, 5000, 20000),
        ]);
    }

    /**
     * Finishing por rango
     */
    public function perRange(): static
    {
        return $this->state(fn (array $attributes) => [
            'measurement_unit' => FinishingMeasurementUnit::RANGO,
        ]);
    }

    /**
     * Finishing de proveedor propio
     */
    public function ownProvider(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_own_provider' => true,
        ]);
    }

    /**
     * Finishing de proveedor tercero
     */
    public function externalProvider(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_own_provider' => false,
        ]);
    }
}

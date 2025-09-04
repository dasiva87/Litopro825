<?php

namespace Database\Factories;

use App\Models\MagazinePage;
use App\Models\MagazineItem;
use App\Models\SimpleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class MagazinePageFactory extends Factory
{
    protected $model = MagazinePage::class;

    public function definition(): array
    {
        return [
            'magazine_item_id' => MagazineItem::factory(),
            'simple_item_id' => SimpleItem::factory(),
            'page_type' => $this->faker->randomElement([
                'portada', 'contraportada', 'interior', 
                'inserto', 'separador', 'anexo'
            ]),
            'page_order' => $this->faker->numberBetween(1, 50),
            'page_quantity' => $this->faker->numberBetween(1, 10),
            'page_notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Cover page
     */
    public function cover(): static
    {
        return $this->state(fn (array $attributes) => [
            'page_type' => 'portada',
            'page_order' => 1,
            'page_quantity' => 1,
        ]);
    }

    /**
     * Back cover page
     */
    public function backCover(): static
    {
        return $this->state(fn (array $attributes) => [
            'page_type' => 'contraportada',
            'page_order' => 999,
            'page_quantity' => 1,
        ]);
    }

    /**
     * Interior page
     */
    public function interior(): static
    {
        return $this->state(fn (array $attributes) => [
            'page_type' => 'interior',
            'page_quantity' => $this->faker->numberBetween(2, 20),
        ]);
    }

    /**
     * Page with specific order
     */
    public function withOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'page_order' => $order,
        ]);
    }

    /**
     * Multiple pages
     */
    public function withQuantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'page_quantity' => $quantity,
        ]);
    }
}
<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\SimpleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentItem>
 */
class DocumentItemFactory extends Factory
{
    protected $model = DocumentItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $document = Document::factory()->create();

        return [
            'document_id' => $document->id,
            'company_id' => $document->company_id,
            'itemable_type' => SimpleItem::class,
            'itemable_id' => SimpleItem::factory(),
            'description' => $this->faker->sentence(6),
            'quantity' => $this->faker->numberBetween(100, 5000),
            'unit_price' => $this->faker->randomFloat(2, 50, 500),
            'total_price' => function (array $attributes) {
                return $attributes['quantity'] * $attributes['unit_price'];
            },
            'profit_margin' => $this->faker->randomFloat(2, 10, 40),
            'item_type' => 'simple_item',
            'order_status' => 'pending',
        ];
    }

    /**
     * Indicate that the document item has no itemable (null itemable).
     */
    public function withoutItemable(): static
    {
        return $this->state(fn (array $attributes) => [
            'itemable_type' => null,
            'itemable_id' => null,
        ]);
    }

    /**
     * Indicate that the document item has custom item type.
     */
    public function customItem(): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => 'custom_item',
            'itemable_type' => null,
            'itemable_id' => null,
        ]);
    }

    /**
     * Indicate that the document item belongs to a specific document.
     */
    public function forDocument(Document $document): static
    {
        return $this->state(fn (array $attributes) => [
            'document_id' => $document->id,
            'company_id' => $document->company_id,
        ]);
    }

    /**
     * Indicate that the document item is ready for orders.
     */
    public function availableForOrders(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'pending',
        ]);
    }
}

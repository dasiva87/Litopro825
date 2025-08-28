<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Company;
use App\Models\User;
use App\Models\Contact;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'contact_id' => Contact::factory()->customer(),
            'document_type_id' => DocumentType::factory(),
            'document_number' => $this->generateDocumentNumber(),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'due_date' => $this->faker->optional(0.7)->dateTimeBetween('now', '+30 days'),
            'status' => $this->faker->randomElement(['draft', 'sent', 'approved', 'in_production', 'completed']),
            'subtotal' => 0, // Se calculará automáticamente
            'tax_percentage' => 19.0, // IVA Colombia
            'tax_amount' => 0, // Se calculará automáticamente
            'total' => 0, // Se calculará automáticamente
            'notes' => $this->faker->optional(0.4)->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function generateDocumentNumber(): string
    {
        $year = date('Y');
        $number = $this->faker->numberBetween(1, 999);
        return "COT-{$year}-" . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    public function quotation(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_number' => $this->generateDocumentNumber(),
            'status' => 'draft',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'due_date' => $this->faker->dateTimeBetween('now', '+15 days'),
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function withItems(): static
    {
        return $this->afterCreating(function (Document $document) {
            // Crear algunos DocumentItems con SimpleItems
            $itemCount = $this->faker->numberBetween(1, 5);
            
            for ($i = 0; $i < $itemCount; $i++) {
                $simpleItem = \App\Models\SimpleItem::factory()->create();
                
                // Calcular precios
                $calculatorService = new \App\Services\SimpleItemCalculatorService();
                $pricing = $calculatorService->calculateFinalPricing($simpleItem);
                
                $document->items()->create([
                    'itemable_type' => 'App\\Models\\SimpleItem',
                    'itemable_id' => $simpleItem->id,
                    'description' => $simpleItem->description,
                    'quantity' => $simpleItem->quantity,
                    'unit_price' => $pricing->unitPrice,
                    'total_price' => $pricing->finalPrice,
                ]);
            }
            
            // Recalcular totales del documento
            $document->recalculateTotals();
        });
    }
}
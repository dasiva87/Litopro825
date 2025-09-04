<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Finishing;
use App\Enums\FinishingMeasurementUnit;

class TalonarioFinishingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $finishings = [
            [
                'name' => 'Numeración Consecutiva',
                'description' => 'Numeración automática consecutiva para talonarios. Se aplica a cada número individual.',
                'measurement_unit' => FinishingMeasurementUnit::POR_NUMERO,
                'unit_price' => 15.00,
                'active' => true
            ],
            [
                'name' => 'Perforación para Desprendimiento',
                'description' => 'Perforación para facilitar el desprendimiento de las hojas. Se aplica por talonario completo.',
                'measurement_unit' => FinishingMeasurementUnit::POR_TALONARIO,
                'unit_price' => 500.00,
                'active' => true
            ],
            [
                'name' => 'Engomado Superior',
                'description' => 'Aplicación de goma en la parte superior del talonario para mantener unidas las hojas.',
                'measurement_unit' => FinishingMeasurementUnit::POR_TALONARIO,
                'unit_price' => 800.00,
                'active' => true
            ],
            [
                'name' => 'Armado en Bloques',
                'description' => 'Armado y empaquetado de los talonarios en bloques para entrega.',
                'measurement_unit' => FinishingMeasurementUnit::POR_TALONARIO,
                'unit_price' => 300.00,
                'active' => true
            ],
            [
                'name' => 'Refuerzo de Lomo',
                'description' => 'Refuerzo del lomo del talonario con cartón o material adicional.',
                'measurement_unit' => FinishingMeasurementUnit::POR_TALONARIO,
                'unit_price' => 400.00,
                'active' => true
            ],
            [
                'name' => 'Talonario con Tapa',
                'description' => 'Agregado de tapa protectora para el talonario.',
                'measurement_unit' => FinishingMeasurementUnit::POR_TALONARIO,
                'unit_price' => 600.00,
                'active' => true
            ]
        ];

        foreach ($finishings as $finishingData) {
            // Verificar si ya existe para evitar duplicados
            $existing = Finishing::where('name', $finishingData['name'])->first();
            
            if (!$existing) {
                // Obtener todas las compañías y crear finishing para cada una
                $companies = \App\Models\Company::all();
                
                foreach ($companies as $company) {
                    Finishing::create(array_merge($finishingData, [
                        'company_id' => $company->id
                    ]));
                }
                
                $this->command->info("✓ Finishing '{$finishingData['name']}' created for all companies");
            } else {
                $this->command->info("- Finishing '{$finishingData['name']}' already exists, skipping");
            }
        }

        $this->command->info("🎉 Talonario finishings seeded successfully!");
    }
}
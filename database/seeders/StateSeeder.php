<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        $colombia = Country::where('code', 'CO')->first();
        
        if (!$colombia) {
            return;
        }

        $states = [
            ['name' => 'Antioquia', 'code' => 'ANT'],
            ['name' => 'Atlántico', 'code' => 'ATL'],
            ['name' => 'Bogotá D.C.', 'code' => 'BOG'],
            ['name' => 'Bolívar', 'code' => 'BOL'],
            ['name' => 'Boyacá', 'code' => 'BOY'],
            ['name' => 'Caldas', 'code' => 'CAL'],
            ['name' => 'Caquetá', 'code' => 'CAQ'],
            ['name' => 'Cauca', 'code' => 'CAU'],
            ['name' => 'Cesar', 'code' => 'CES'],
            ['name' => 'Córdoba', 'code' => 'COR'],
            ['name' => 'Cundinamarca', 'code' => 'CUN'],
            ['name' => 'Huila', 'code' => 'HUI'],
            ['name' => 'La Guajira', 'code' => 'LAG'],
            ['name' => 'Magdalena', 'code' => 'MAG'],
            ['name' => 'Meta', 'code' => 'MET'],
            ['name' => 'Nariño', 'code' => 'NAR'],
            ['name' => 'Norte de Santander', 'code' => 'NSA'],
            ['name' => 'Quindío', 'code' => 'QUI'],
            ['name' => 'Risaralda', 'code' => 'RIS'],
            ['name' => 'Santander', 'code' => 'SAN'],
            ['name' => 'Sucre', 'code' => 'SUC'],
            ['name' => 'Tolima', 'code' => 'TOL'],
            ['name' => 'Valle del Cauca', 'code' => 'VAC'],
        ];

        foreach ($states as $state) {
            State::firstOrCreate(
                [
                    'country_id' => $colombia->id,
                    'code' => $state['code']
                ],
                [
                    'country_id' => $colombia->id,
                    'name' => $state['name'],
                    'code' => $state['code'],
                    'is_active' => true,
                ]
            );
        }
    }
}
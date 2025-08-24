<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\State;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $citiesData = [
            'ANT' => ['Medellín', 'Bello', 'Itagüí', 'Envigado', 'Apartadó'],
            'ATL' => ['Barranquilla', 'Soledad', 'Malambo', 'Galapa'],
            'BOG' => ['Bogotá'],
            'BOL' => ['Cartagena', 'Magangué', 'Turbaco'],
            'BOY' => ['Tunja', 'Duitama', 'Sogamoso', 'Chiquinquirá'],
            'CAL' => ['Manizales', 'Chinchiná', 'Villamaría'],
            'CUN' => ['Soacha', 'Girardot', 'Zipaquirá', 'Facatativá', 'Chía'],
            'HUI' => ['Neiva', 'Pitalito', 'Garzón'],
            'MAG' => ['Santa Marta', 'Ciénaga'],
            'MET' => ['Villavicencio', 'Acacías', 'Granada'],
            'NAR' => ['Pasto', 'Tumaco', 'Ipiales'],
            'NSA' => ['Cúcuta', 'Ocaña', 'Pamplona'],
            'QUI' => ['Armenia', 'Calarcá', 'La Tebaida'],
            'RIS' => ['Pereira', 'Dosquebradas', 'Santa Rosa de Cabal'],
            'SAN' => ['Bucaramanga', 'Floridablanca', 'Girón', 'Piedecuesta'],
            'TOL' => ['Ibagué', 'Espinal', 'Melgar'],
            'VAC' => ['Cali', 'Palmira', 'Buenaventura', 'Cartago', 'Buga'],
        ];

        foreach ($citiesData as $stateCode => $cities) {
            $state = State::where('code', $stateCode)->first();
            
            if (!$state) {
                continue;
            }

            foreach ($cities as $cityName) {
                City::firstOrCreate(
                    [
                        'state_id' => $state->id,
                        'name' => $cityName
                    ],
                    [
                        'state_id' => $state->id,
                        'name' => $cityName,
                        'code' => null,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
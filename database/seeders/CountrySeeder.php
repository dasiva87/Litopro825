<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            [
                'name' => 'Colombia',
                'code' => 'CO',
                'phone_code' => '+57',
                'is_active' => true,
            ],
            [
                'name' => 'Estados Unidos',
                'code' => 'US',
                'phone_code' => '+1',
                'is_active' => true,
            ],
            [
                'name' => 'México',
                'code' => 'MX',
                'phone_code' => '+52',
                'is_active' => true,
            ],
            [
                'name' => 'España',
                'code' => 'ES',
                'phone_code' => '+34',
                'is_active' => true,
            ],
        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(
                ['code' => $country['code']],
                $country
            );
        }
    }
}
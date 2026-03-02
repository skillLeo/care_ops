<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Apartment;
use App\Models\House;
use App\Models\LevelOfCare;

class ApartmentsTableSeeder extends Seeder
{
    public function run(): void
    {
        $houses = House::all();
        $levelOfCareIds = LevelOfCare::pluck('id')->all();

        foreach ($houses as $house) {
            for ($i = 1; $i <= 5; $i++) {
                Apartment::create([
                    'house_id' => $house->id,
                    'level_of_care' => $levelOfCareIds[array_rand($levelOfCareIds)],
                    'apartment_number' => "Apt $i",
                    'capacity' => rand(1, 4)
                ]);
            }
        }
    }
}

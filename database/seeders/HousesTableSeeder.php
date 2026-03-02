<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\House;

class HousesTableSeeder extends Seeder
{
    public function run(): void
    {
        $houses = [
            ['house_name' => 'Green Villa', 'house_address' => '123 Green St'],
            ['house_name' => 'Blue Cottage', 'house_address' => '456 Blue Ave'],
            ['house_name' => 'Red Manor', 'house_address' => '789 Red Blvd'],
        ];

        foreach ($houses as $house) {
            House::create($house);
        }
    }
}

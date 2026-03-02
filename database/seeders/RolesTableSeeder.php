<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('roles')->insert([
            ['name' => 'admin', 'display_name' => 'Admin'],
            ['name' => 'counselor', 'display_name' => 'Counselor'],
            ['name' => 'peer', 'display_name' => 'Peer'],
            ['name' => 'auditor', 'display_name' => 'Auditor'],
            ['name' => 'clinical_director', 'display_name' => 'Clinical Director'],
            ['name' => 'general_manager', 'display_name' => 'General Manager'],
            ['name' => 'ceo', 'display_name' => 'CEO'],
        ]);
    }
}

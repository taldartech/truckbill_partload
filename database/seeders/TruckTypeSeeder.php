<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TruckTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Open',
            'Closed',
            'Container',
            'Trailer',
            'Tanker',
            'Refrigerated',
            'Flatbed',
            'Tipper',
            'Other',
        ];

        foreach ($types as $type) {
            \App\Models\TruckType::firstOrCreate(['name' => $type]);
        }
    }
}

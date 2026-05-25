<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatesSeeder extends Seeder
{
    public function run(): void
    {
        $india = DB::table('countries')->where('name', 'India')->first();
        if ($india === null) {
            $this->command?->warn('India country not found. Run CountriesSeeder first.');

            return;
        }

        $now = now();

        foreach (IndiaGeoData::statesWithDistricts() as $row) {
            $existing = DB::table('states')
                ->where('country_id', $india->id)
                ->where('name', $row['name'])
                ->first();

            if ($existing) {
                DB::table('states')
                    ->where('id', $existing->id)
                    ->update([
                        'code' => $row['code'],
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('states')->insert([
                    'country_id' => $india->id,
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}

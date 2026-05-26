<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitiesSeeder extends Seeder
{
    public function run(): void
    {
        $india = DB::table('countries')->where('name', 'India')->first();
        if ($india === null) {
            $this->command?->warn('India country not found. Run CountriesSeeder first.');

            return;
        }

        $company = DB::table('companies')->first();
        $companyId = $company ? $company->id : 1;

        $now = now();

        foreach (IndiaGeoData::statesWithDistricts() as $stateRow) {
            $state = DB::table('states')
                ->where('country_id', $india->id)
                ->where('name', $stateRow['name'])
                ->first();

            if ($state === null) {
                $this->command?->warn("State not found, skipping cities: {$stateRow['name']}");

                continue;
            }

            foreach ($stateRow['districts'] as $districtName) {
                if ($districtName === '') {
                    continue;
                }

                $existingCity = DB::table('cities')
                    ->where('state_id', $state->id)
                    ->where('name', $districtName)
                    ->first();

                if ($existingCity) {
                    DB::table('cities')
                        ->where('id', $existingCity->id)
                        ->update([
                            'country_id' => $india->id,
                            'updated_at' => $now,
                        ]);
                    $cityId = $existingCity->id;
                } else {
                    $cityId = DB::table('cities')->insertGetId([
                        'state_id' => $state->id,
                        'country_id' => $india->id,
                        'name' => $districtName,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $existingStation = DB::table('stations')
                    ->where('company_id', $companyId)
                    ->where('name', $districtName)
                    ->where('city_id', $cityId)
                    ->first();

                if ($existingStation) {
                    DB::table('stations')
                        ->where('id', $existingStation->id)
                        ->update([
                            'state_id' => $state->id,
                            'country_id' => $india->id,
                            'updated_at' => $now,
                        ]);
                } else {
                    DB::table('stations')->insert([
                        'company_id' => $companyId,
                        'country_id' => $india->id,
                        'state_id' => $state->id,
                        'city_id' => $cityId,
                        'name' => $districtName,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}

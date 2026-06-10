<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\TruckType;
use Illuminate\Database\Seeder;

class TruckTypeSeeder extends Seeder
{
    public function run(): void
    {
        $ownerIds = Company::query()
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        if ($ownerIds->isEmpty()) {
            return;
        }

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

        foreach ($ownerIds as $ownerId) {
            foreach ($types as $type) {
                TruckType::firstOrCreate([
                    'owner_id' => $ownerId,
                    'name' => $type,
                ]);
            }
        }
    }
}

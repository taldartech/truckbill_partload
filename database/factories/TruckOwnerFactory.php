<?php

namespace Database\Factories;

use App\Models\TruckOwner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TruckOwner>
 */
class TruckOwnerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'address' => fake()->streetAddress(),
            'mobile' => '9'.fake()->numerify('#########'),
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}

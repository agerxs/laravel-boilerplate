<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Locality>
 */
class LocalityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->city(),
            'code_officiel' => fake()->unique()->numerify('LOC###'),
            'locality_type_id' => \App\Models\LocalityType::factory(),
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

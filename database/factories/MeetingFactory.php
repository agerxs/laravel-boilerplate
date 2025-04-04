<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meeting>
 */
class MeetingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'status' => 'scheduled',
            'local_committee_id' => 1,
            'scheduled_date' => fake()->dateTimeBetween('-1 year', '+1 year'),
            'scheduled_time' => fake()->time(),
            'target_enrollments' => fake()->numberBetween(10, 100),
            'actual_enrollments' => fake()->numberBetween(0, 50),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

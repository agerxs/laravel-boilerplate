<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeetingAttendee>
 */
class MeetingAttendeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => \App\Models\Meeting::factory(),
            'localite_id' => \App\Models\Locality::factory(),
            'representative_id' => null,
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'role' => fake()->randomElement(['member', 'guest', 'observer']),
            'is_expected' => true,
            'is_present' => false,
            'attendance_status' => 'expected',
            'replacement_name' => null,
            'replacement_phone' => null,
            'replacement_role' => null,
            'comments' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

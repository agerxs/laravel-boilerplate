<?php

namespace Database\Factories;

use App\Models\LocalCommittee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LocalCommitteeMember>
 */
class LocalCommitteeMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'local_committee_id' => LocalCommittee::factory(),
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
            'role' => fake()->randomElement(['secretary', 'president', 'member']),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }
} 
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeetingPaymentItem>
 */
class MeetingPaymentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_payment_list_id' => \App\Models\MeetingPaymentList::factory(),
            'attendee_id' => \App\Models\MeetingAttendee::factory(),
            'amount' => fake()->randomFloat(2, 1000, 10000),
            'role' => fake()->randomElement(['member', 'guest', 'observer']),
            'payment_status' => 'pending',
            'payment_method' => null,
            'payment_date' => null,
            'comments' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

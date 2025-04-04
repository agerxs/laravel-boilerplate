<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeetingPaymentList>
 */
class MeetingPaymentListFactory extends Factory
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
            'submitted_by' => null,
            'validated_by' => null,
            'submitted_at' => null,
            'validated_at' => null,
            'status' => 'draft',
            'rejection_reason' => null,
            'total_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

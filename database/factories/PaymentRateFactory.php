<?php

namespace Database\Factories;

use App\Models\PaymentRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentRateFactory extends Factory
{
    protected $model = PaymentRate::class;

    public function definition()
    {
        return [
            'role' => $this->faker->randomElement(['prefet', 'sous_prefet', 'secretaire', 'representant']),
            'meeting_rate' => $this->faker->numberBetween(5000, 25000),
            'notes' => $this->faker->sentence(),
            'is_active' => $this->faker->boolean(),
        ];
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }
} 
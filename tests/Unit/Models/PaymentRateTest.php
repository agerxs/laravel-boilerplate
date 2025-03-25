<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\PaymentRate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentRateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_active_rate_for_role()
    {
        // Créer un taux actif
        $activeRate = PaymentRate::factory()->create([
            'role' => 'prefet',
            'meeting_rate' => 15000,
            'is_active' => true
        ]);

        // Créer un taux inactif
        PaymentRate::factory()->create([
            'role' => 'prefet',
            'meeting_rate' => 10000,
            'is_active' => false
        ]);

        $result = PaymentRate::getActiveRateForRole('prefet');

        $this->assertEquals($activeRate->id, $result->id);
        $this->assertEquals(15000, $result->meeting_rate);
    }

    /** @test */
    public function it_ensures_only_one_active_rate_per_role()
    {
        // Créer un premier taux actif
        PaymentRate::factory()->create([
            'role' => 'prefet',
            'meeting_rate' => 15000,
            'is_active' => true
        ]);

        // Créer un second taux actif pour le même rôle
        $newRate = PaymentRate::factory()->create([
            'role' => 'prefet',
            'meeting_rate' => 20000,
            'is_active' => true
        ]);

        // Vérifier qu'il n'y a qu'un seul taux actif
        $activeRates = PaymentRate::where('role', 'prefet')
            ->where('is_active', true)
            ->count();

        $this->assertEquals(1, $activeRates);
        $this->assertTrue($newRate->fresh()->is_active);
    }
} 
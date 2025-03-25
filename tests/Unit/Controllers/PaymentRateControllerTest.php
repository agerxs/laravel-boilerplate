<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\PaymentRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentRateControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_store_new_payment_rate()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('payment-rates.store'), [
            'role' => 'prefet',
            'meeting_rate' => 15000,
            'notes' => 'Test notes',
            'is_active' => true
        ]);

        $response->assertRedirect(route('payment-rates.index'));
        
        $this->assertDatabaseHas('payment_rates', [
            'role' => 'prefet',
            'meeting_rate' => 15000,
            'notes' => 'Test notes',
            'is_active' => true
        ]);
    }

    /** @test */
    public function it_can_update_payment_rate()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $paymentRate = PaymentRate::factory()->create([
            'role' => 'prefet',
            'meeting_rate' => 15000
        ]);

        $response = $this->put(route('payment-rates.update', $paymentRate), [
            'role' => 'prefet',
            'meeting_rate' => 20000,
            'notes' => 'Updated notes',
            'is_active' => true
        ]);

        $response->assertRedirect(route('payment-rates.index'));
        
        $this->assertDatabaseHas('payment_rates', [
            'id' => $paymentRate->id,
            'meeting_rate' => 20000,
            'notes' => 'Updated notes'
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('payment-rates.store'), [
            'role' => '',
            'meeting_rate' => '',
        ]);

        $response->assertSessionHasErrors(['role', 'meeting_rate']);
    }
} 
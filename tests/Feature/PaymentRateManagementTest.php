<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PaymentRate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentRateManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_view_payment_rates_list()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        PaymentRate::factory()->count(3)->create();

        $response = $this->get(route('payment-rates.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('PaymentRates/Index')
            ->has('paymentRates')
        );
    }

    /** @test */
    public function admin_can_create_payment_rate()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $response = $this->post(route('payment-rates.store'), [
            'role' => 'prefet',
            'meeting_rate' => 15000,
            'notes' => 'Test rate',
            'is_active' => true
        ]);

        $response->assertRedirect(route('payment-rates.index'));
        $this->assertDatabaseHas('payment_rates', [
            'role' => 'prefet',
            'meeting_rate' => 15000
        ]);
    }

    /** @test */
    public function admin_can_update_payment_rate()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $paymentRate = PaymentRate::factory()->create();

        $response = $this->put(route('payment-rates.update', $paymentRate), [
            'role' => 'secretaire',
            'meeting_rate' => 10000,
            'notes' => 'Updated rate',
            'is_active' => true
        ]);

        $response->assertRedirect(route('payment-rates.index'));
        $this->assertDatabaseHas('payment_rates', [
            'id' => $paymentRate->id,
            'role' => 'secretaire',
            'meeting_rate' => 10000
        ]);
    }

    /** @test */
    public function admin_can_delete_payment_rate()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $paymentRate = PaymentRate::factory()->create();

        $response = $this->delete(route('payment-rates.destroy', $paymentRate));

        $response->assertRedirect(route('payment-rates.index'));
        $this->assertDatabaseMissing('payment_rates', [
            'id' => $paymentRate->id
        ]);
    }
} 
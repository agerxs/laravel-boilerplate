<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\Meeting;
use App\Models\User;
use App\Models\PaymentRate;
use App\Models\MeetingPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MeetingPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_show_meeting_payments()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $meeting = Meeting::factory()->create();
        $paymentRate = PaymentRate::factory()->create([
            'role' => 'prefet',
            'meeting_rate' => 15000,
            'is_active' => true
        ]);

        $response = $this->get(route('meeting-payments.show', $meeting));

        $response->assertInertia(fn ($page) => $page
            ->component('MeetingPayments/Show')
            ->has('meeting')
            ->has('paymentData')
        );
    }

    /** @test */
    public function it_can_process_meeting_payments()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $meeting = Meeting::factory()->create();
        $official = User::factory()->create();

        $response = $this->post(route('meeting-payments.process', $meeting), [
            'payments' => [
                [
                    'user_id' => $official->id,
                    'role' => 'prefet',
                    'amount' => 15000,
                    'is_paid' => true,
                    'payment_date' => '2024-03-14',
                    'payment_method' => 'especes',
                    'notes' => 'Test payment'
                ]
            ]
        ]);

        $response->assertRedirect(route('meeting-payments.show', $meeting));
        
        $this->assertDatabaseHas('meeting_payments', [
            'meeting_id' => $meeting->id,
            'user_id' => $official->id,
            'role' => 'prefet',
            'amount' => 15000,
            'is_paid' => true
        ]);
    }

    /** @test */
    public function it_validates_payment_processing()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $meeting = Meeting::factory()->create();

        $response = $this->post(route('meeting-payments.process', $meeting), [
            'payments' => [
                [
                    'user_id' => '',
                    'amount' => '',
                    'is_paid' => ''
                ]
            ]
        ]);

        $response->assertSessionHasErrors([
            'payments.0.user_id',
            'payments.0.role',
            'payments.0.amount',
            'payments.0.is_paid'
        ]);
    }
} 
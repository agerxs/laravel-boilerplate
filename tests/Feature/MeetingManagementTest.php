<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Meeting;
use App\Models\LocalCommittee;
use App\Models\PaymentRate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MeetingManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authorized_user_can_create_meeting()
    {
        $user = User::factory()->create()->assignRole('admin');
        $committee = LocalCommittee::factory()->create();
        
        $this->actingAs($user);

        $response = $this->post(route('meetings.store'), [
            'title' => 'Réunion test',
            'local_committee_id' => $committee->id,
            'scheduled_date' => now()->addDays(7)->format('Y-m-d'),
            'location' => 'Salle de réunion',
            'description' => 'Description de la réunion',
            'target_enrollments' => 100,
            'actual_enrollments' => 75
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('meetings', [
            'title' => 'Réunion test',
            'local_committee_id' => $committee->id,
            'target_enrollments' => 100,
            'actual_enrollments' => 75
        ]);
    }

    /** @test */
    public function it_can_process_meeting_payments()
    {
        $user = User::factory()->create()->assignRole('admin');
        $meeting = Meeting::factory()->create();
        $official = User::factory()->create();
        
        PaymentRate::factory()->create([
            'role' => 'prefet',
            'meeting_rate' => 15000,
            'is_active' => true
        ]);

        $this->actingAs($user);

        $response = $this->post(route('meeting-payments.process', $meeting), [
            'payments' => [
                [
                    'user_id' => $official->id,
                    'role' => 'prefet',
                    'amount' => 15000,
                    'is_paid' => true,
                    'payment_date' => now()->format('Y-m-d'),
                    'payment_method' => 'especes'
                ]
            ]
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('meeting_payments', [
            'meeting_id' => $meeting->id,
            'user_id' => $official->id,
            'amount' => 15000,
            'is_paid' => true
        ]);
    }

    /** @test */
    public function it_validates_payment_data()
    {
        $user = User::factory()->create()->assignRole('admin');
        $meeting = Meeting::factory()->create();
        
        $this->actingAs($user);

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
            'payments.0.amount'
        ]);
    }

    /** @test */
    public function it_validates_enrollment_numbers()
    {
        $user = User::factory()->create()->assignRole('admin');
        $committee = LocalCommittee::factory()->create();
        
        $this->actingAs($user);

        $response = $this->post(route('meetings.store'), [
            'title' => 'Réunion test',
            'local_committee_id' => $committee->id,
            'target_enrollments' => -1,
            'actual_enrollments' => 'abc'
        ]);

        $response->assertSessionHasErrors([
            'target_enrollments',
            'actual_enrollments'
        ]);
    }

    /** @test */
    public function actual_enrollments_cannot_exceed_target_enrollments()
    {
        $user = User::factory()->create()->assignRole('admin');
        $committee = LocalCommittee::factory()->create();
        
        $this->actingAs($user);

        $response = $this->post(route('meetings.store'), [
            'title' => 'Réunion test',
            'local_committee_id' => $committee->id,
            'target_enrollments' => 100,
            'actual_enrollments' => 150
        ]);

        $response->assertSessionHasErrors('actual_enrollments');
    }
} 
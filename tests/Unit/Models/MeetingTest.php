<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Meeting;
use App\Models\LocalCommittee;
use App\Models\User;
use App\Models\MeetingAttendee;
use App\Models\MeetingPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MeetingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_local_committee()
    {
        $committee = LocalCommittee::factory()->create();
        $meeting = Meeting::factory()->create([
            'local_committee_id' => $committee->id
        ]);

        $this->assertInstanceOf(LocalCommittee::class, $meeting->localCommittee);
        $this->assertEquals($committee->id, $meeting->localCommittee->id);
    }

    /** @test */
    public function it_can_have_attendees()
    {
        $meeting = Meeting::factory()->create();
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            MeetingAttendee::create([
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'status' => 'present'
            ]);
        }

        $this->assertEquals(3, $meeting->attendees()->count());
    }

    /** @test */
    public function it_can_have_payments()
    {
        $meeting = Meeting::factory()->create();
        $users = User::factory()->count(2)->create();

        foreach ($users as $user) {
            MeetingPayment::create([
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'amount' => 15000,
                'is_paid' => true,
                'payment_date' => now(),
                'payment_method' => 'especes'
            ]);
        }

        $this->assertEquals(2, $meeting->payments()->count());
    }

    /** @test */
    public function it_can_calculate_total_payments()
    {
        $meeting = Meeting::factory()->create();
        
        MeetingPayment::create([
            'meeting_id' => $meeting->id,
            'user_id' => User::factory()->create()->id,
            'amount' => 15000,
            'is_paid' => true
        ]);

        MeetingPayment::create([
            'meeting_id' => $meeting->id,
            'user_id' => User::factory()->create()->id,
            'amount' => 10000,
            'is_paid' => true
        ]);

        $this->assertEquals(25000, $meeting->totalPayments());
    }

    /** @test */
    public function it_can_check_if_all_payments_are_completed()
    {
        $meeting = Meeting::factory()->create();
        
        MeetingPayment::create([
            'meeting_id' => $meeting->id,
            'user_id' => User::factory()->create()->id,
            'amount' => 15000,
            'is_paid' => true
        ]);

        MeetingPayment::create([
            'meeting_id' => $meeting->id,
            'user_id' => User::factory()->create()->id,
            'amount' => 10000,
            'is_paid' => false
        ]);

        $this->assertFalse($meeting->allPaymentsCompleted());

        // Marquer le deuxième paiement comme payé
        $meeting->payments()->where('is_paid', false)->update(['is_paid' => true]);

        $this->assertTrue($meeting->fresh()->allPaymentsCompleted());
    }
} 
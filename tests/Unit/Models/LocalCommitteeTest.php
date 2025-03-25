<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\LocalCommittee;
use App\Models\User;
use App\Models\Meeting;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocalCommitteeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_local_committee()
    {
        $committee = LocalCommittee::factory()->create([
            'name' => 'Comité Local de Test',
            'locality_id' => 1,
            'status' => 'active'
        ]);

        $this->assertDatabaseHas('local_committees', [
            'name' => 'Comité Local de Test',
            'status' => 'active'
        ]);
    }

    /** @test */
    public function it_can_have_permanent_members()
    {
        $committee = LocalCommittee::factory()->create();
        $president = User::factory()->create();
        $secretary = User::factory()->create();

        $committee->members()->createMany([
            [
                'user_id' => $president->id,
                'role' => 'president',
                'status' => 'active'
            ],
            [
                'user_id' => $secretary->id,
                'role' => 'secretary',
                'status' => 'active'
            ]
        ]);

        $this->assertEquals(2, $committee->members()->count());
        $this->assertTrue($committee->members()->where('role', 'president')->exists());
        $this->assertTrue($committee->members()->where('role', 'secretary')->exists());
    }

    /** @test */
    public function it_can_have_village_representatives()
    {
        $committee = LocalCommittee::factory()->create();
        $village = Village::factory()->create();

        $committee->villageRepresentatives()->create([
            'village_id' => $village->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role' => 'Chef du village',
            'phone' => '1234567890'
        ]);

        $this->assertEquals(1, $committee->villageRepresentatives()->count());
        $this->assertEquals('John', $committee->villageRepresentatives()->first()->first_name);
    }

    /** @test */
    public function it_can_have_meetings()
    {
        $committee = LocalCommittee::factory()->create();
        $meetings = Meeting::factory()->count(3)->create([
            'local_committee_id' => $committee->id
        ]);

        $this->assertEquals(3, $committee->meetings()->count());
    }

    /** @test */
    public function it_can_get_active_committees()
    {
        LocalCommittee::factory()->count(3)->create(['status' => 'active']);
        LocalCommittee::factory()->count(2)->create(['status' => 'inactive']);

        $activeCommittees = LocalCommittee::active()->get();

        $this->assertEquals(3, $activeCommittees->count());
    }
} 
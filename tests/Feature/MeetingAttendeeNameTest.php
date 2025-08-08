<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Meeting;
use App\Models\User;
use App\Models\LocalCommittee;
use App\Models\Locality;
use App\Models\Representative;
use App\Models\MeetingAttendee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class MeetingAttendeeNameTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $localCommittee;
    protected $meeting;
    protected $representative;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->localCommittee = LocalCommittee::factory()->create();
        
        // Créer une localité et un village
        $locality = Locality::factory()->create([
            'name' => 'Sous-préfecture Test',
            'type_id' => 2, // Type sous-préfecture
        ]);
        
        $village = Locality::factory()->create([
            'name' => 'Village Test',
            'parent_id' => $locality->id,
            'type_id' => 3, // Type village
        ]);
        
        // Créer un représentant
        $this->representative = Representative::factory()->create([
            'name' => 'Jean Dupont',
            'phone' => '123456789',
            'role' => 'Président',
            'locality_id' => $village->id,
        ]);
        
        // Créer une réunion
        $this->meeting = Meeting::factory()->create([
            'local_committee_id' => $this->localCommittee->id,
            'status' => 'planned',
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_displays_representative_name_when_representative_is_linked()
    {
        // Créer un participant avec un représentant lié
        $attendee = MeetingAttendee::create([
            'meeting_id' => $this->meeting->id,
            'representative_id' => $this->representative->id,
            'localite_id' => $this->representative->locality_id,
            'name' => 'Nom direct', // Ce nom ne devrait pas être utilisé
            'phone' => '987654321',
            'role' => 'Rôle direct',
            'attendance_status' => 'expected',
            'is_expected' => true,
            'is_present' => false,
        ]);

        // Charger la relation
        $attendee->load('representative');

        // Vérifier que le nom du représentant est utilisé
        $this->assertEquals('Jean Dupont', $attendee->name);
        $this->assertEquals('123456789', $attendee->phone);
        $this->assertEquals('Président', $attendee->role);
    }

    /** @test */
    public function it_displays_direct_name_when_no_representative_is_linked()
    {
        // Créer un participant sans représentant lié
        $attendee = MeetingAttendee::create([
            'meeting_id' => $this->meeting->id,
            'representative_id' => null,
            'localite_id' => 1,
            'name' => 'Pierre Martin',
            'phone' => '555555555',
            'role' => 'Secrétaire',
            'attendance_status' => 'expected',
            'is_expected' => true,
            'is_present' => false,
        ]);

        // Vérifier que le nom direct est utilisé
        $this->assertEquals('Pierre Martin', $attendee->name);
        $this->assertEquals('555555555', $attendee->phone);
        $this->assertEquals('Secrétaire', $attendee->role);
    }

    /** @test */
    public function it_displays_fallback_name_when_no_data_available()
    {
        // Créer un participant sans nom ni représentant
        $attendee = MeetingAttendee::create([
            'meeting_id' => $this->meeting->id,
            'representative_id' => null,
            'localite_id' => 1,
            'name' => null,
            'phone' => null,
            'role' => null,
            'attendance_status' => 'expected',
            'is_expected' => true,
            'is_present' => false,
        ]);

        // Vérifier que le nom par défaut est utilisé
        $this->assertEquals('Nom non défini', $attendee->name);
        $this->assertNull($attendee->phone);
        $this->assertEquals('Rôle non défini', $attendee->role);
    }

    /** @test */
    public function it_constructs_name_from_first_and_last_name()
    {
        // Créer un représentant avec first_name et last_name
        $representative = Representative::factory()->create([
            'first_name' => 'Marie',
            'last_name' => 'Durand',
            'name' => null, // Pas de nom direct
            'phone' => '111111111',
            'role' => 'Trésorier',
            'locality_id' => 1,
        ]);

        // Créer un participant avec ce représentant
        $attendee = MeetingAttendee::create([
            'meeting_id' => $this->meeting->id,
            'representative_id' => $representative->id,
            'localite_id' => 1,
            'name' => null,
            'phone' => null,
            'role' => null,
            'attendance_status' => 'expected',
            'is_expected' => true,
            'is_present' => false,
        ]);

        // Charger la relation
        $attendee->load('representative');

        // Vérifier que le nom est construit à partir de first_name et last_name
        $this->assertEquals('Marie Durand', $attendee->name);
        $this->assertEquals('111111111', $attendee->phone);
        $this->assertEquals('Trésorier', $attendee->role);
    }
} 
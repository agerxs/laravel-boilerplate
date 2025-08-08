<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Meeting;
use App\Models\User;
use App\Models\LocalCommittee;
use App\Models\Locality;
use App\Models\Representative;
use App\Models\MeetingAttendee;
use App\Services\MeetingSplitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class MeetingSplitTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $localCommittee;
    protected $parentMeeting;
    protected $splitService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->localCommittee = LocalCommittee::factory()->create();
        
        // Créer une localité (sous-préfecture)
        $locality = Locality::factory()->create([
            'name' => 'Sous-préfecture Test',
            'type_id' => 2, // Type sous-préfecture
        ]);
        
        // Créer des villages
        $village1 = Locality::factory()->create([
            'name' => 'Village A',
            'parent_id' => $locality->id,
            'type_id' => 3, // Type village
        ]);
        
        $village2 = Locality::factory()->create([
            'name' => 'Village B',
            'parent_id' => $locality->id,
            'type_id' => 3, // Type village
        ]);
        
        $village3 = Locality::factory()->create([
            'name' => 'Village C',
            'parent_id' => $locality->id,
            'type_id' => 3, // Type village
        ]);
        
        // Créer des représentants pour certains villages
        $representative1 = Representative::factory()->create([
            'locality_id' => $village1->id,
            'name' => 'Représentant Village A',
        ]);
        
        $representative2 = Representative::factory()->create([
            'locality_id' => $village2->id,
            'name' => 'Représentant Village B',
        ]);
        
        // Pas de représentant pour le Village C
        
        // Créer une réunion parent
        $this->parentMeeting = Meeting::factory()->create([
            'local_committee_id' => $this->localCommittee->id,
            'status' => 'planned',
            'created_by' => $this->user->id,
        ]);
        
        $this->splitService = new MeetingSplitService();
    }

    /** @test */
    public function it_creates_participants_only_for_villages_with_representatives()
    {
        $this->actingAs($this->user);

        // Données pour le split
        $subMeetingsData = [
            [
                'villages' => [
                    ['id' => 1, 'name' => 'Village A'], // Avec représentant
                    ['id' => 2, 'name' => 'Village B'], // Avec représentant
                    ['id' => 3, 'name' => 'Village C'], // Sans représentant
                ],
                'location' => 'Salle de réunion',
            ]
        ];

        // Exécuter le split
        $subMeetings = $this->splitService->splitMeeting($this->parentMeeting, $subMeetingsData);

        // Vérifier qu'une sous-réunion a été créée
        $this->assertCount(1, $subMeetings);
        $subMeeting = $subMeetings[0];

        // Vérifier que seuls 2 participants ont été créés (pour les villages avec représentants)
        $attendees = MeetingAttendee::where('meeting_id', $subMeeting->id)->get();
        $this->assertCount(2, $attendees);

        // Vérifier que les participants correspondent aux représentants
        $attendeeVillageIds = $attendees->pluck('localite_id')->toArray();
        $this->assertContains(1, $attendeeVillageIds); // Village A
        $this->assertContains(2, $attendeeVillageIds); // Village B
        $this->assertNotContains(3, $attendeeVillageIds); // Village C (pas de représentant)

        // Vérifier que le nombre de participants attendus est correct
        $this->assertEquals(2, $subMeeting->target_enrollments);
    }

    /** @test */
    public function it_copies_existing_attendees_from_parent_meeting()
    {
        $this->actingAs($this->user);

        // Créer des participants existants dans la réunion parent
        MeetingAttendee::create([
            'meeting_id' => $this->parentMeeting->id,
            'localite_id' => 1,
            'representative_id' => 1,
            'name' => 'Participant existant',
            'phone' => '123456789',
            'role' => 'Président',
            'attendance_status' => 'expected',
            'is_expected' => true,
            'is_present' => false,
        ]);

        // Données pour le split
        $subMeetingsData = [
            [
                'villages' => [
                    ['id' => 1, 'name' => 'Village A'],
                ],
                'location' => 'Salle de réunion',
            ]
        ];

        // Exécuter le split
        $subMeetings = $this->splitService->splitMeeting($this->parentMeeting, $subMeetingsData);

        // Vérifier que le participant existant a été copié
        $attendees = MeetingAttendee::where('meeting_id', $subMeetings[0]->id)->get();
        $this->assertCount(1, $attendees);
        
        $attendee = $attendees->first();
        $this->assertEquals('Participant existant', $attendee->name);
        $this->assertEquals(1, $attendee->representative_id);
    }

    /** @test */
    public function it_creates_participants_for_all_representatives()
    {
        $this->actingAs($this->user);

        // Créer un représentant (tous les représentants sont considérés comme actifs)
        Representative::factory()->create([
            'locality_id' => 1,
            'name' => 'Représentant test',
        ]);

        // Données pour le split
        $subMeetingsData = [
            [
                'villages' => [
                    ['id' => 1, 'name' => 'Village A'],
                ],
                'location' => 'Salle de réunion',
            ]
        ];

        // Exécuter le split
        $subMeetings = $this->splitService->splitMeeting($this->parentMeeting, $subMeetingsData);

        // Vérifier qu'un participant a été créé pour le représentant
        $attendees = MeetingAttendee::where('meeting_id', $subMeetings[0]->id)->get();
        $this->assertCount(1, $attendees);
    }
} 
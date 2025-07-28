<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LocalCommittee;
use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class MultipleMeetingsCreationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authorized_user_can_access_multiple_creation_page()
    {
        $user = User::factory()->create()->assignRole('admin');
        $committee = LocalCommittee::factory()->create();
        
        $this->actingAs($user);

        $response = $this->get(route('meetings.create-multiple'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Meetings/CreateMultiple')
        );
    }

    #[Test]
    public function authorized_user_can_create_multiple_meetings()
    {
        $user = User::factory()->create()->assignRole('admin');
        $committee = LocalCommittee::factory()->create();
        
        $this->actingAs($user);

        $meetingsData = [
            'local_committee_id' => $committee->id,
            'meetings' => [
                [
                    'title' => 'Réunion mensuelle du comité - janvier 2024',
                    'scheduled_date' => '2024-01-01',
                    'scheduled_time' => '14:00',
                    'location' => 'Salle de réunion'
                ],
                [
                    'title' => 'Réunion mensuelle du comité - février 2024',
                    'scheduled_date' => '2024-02-01',
                    'scheduled_time' => '14:00',
                    'location' => 'Salle de réunion'
                ],
                [
                    'title' => 'Réunion mensuelle du comité - mars 2024',
                    'scheduled_date' => '2024-03-01',
                    'scheduled_time' => '14:00',
                    'location' => 'Salle de réunion'
                ],
                [
                    'title' => 'Réunion mensuelle du comité - avril 2024',
                    'scheduled_date' => '2024-04-01',
                    'scheduled_time' => '14:00',
                    'location' => 'Salle de réunion'
                ],
                [
                    'title' => 'Réunion mensuelle du comité - mai 2024',
                    'scheduled_date' => '2024-05-01',
                    'scheduled_time' => '14:00',
                    'location' => 'Salle de réunion'
                ],
                [
                    'title' => 'Réunion mensuelle du comité - juin 2024',
                    'scheduled_date' => '2024-06-01',
                    'scheduled_time' => '14:00',
                    'location' => 'Salle de réunion'
                ]
            ]
        ];

        $response = $this->post(route('meetings.store-multiple'), $meetingsData);

        $response->assertRedirect(route('meetings.index'));
        
        // Vérifier que 6 réunions ont été créées
        $this->assertEquals(6, Meeting::count());
        
        // Vérifier que toutes les réunions ont le bon comité local
        $this->assertEquals(6, Meeting::where('local_committee_id', $committee->id)->count());
        
        // Vérifier que toutes les réunions ont le statut 'scheduled'
        $this->assertEquals(6, Meeting::where('status', 'scheduled')->count());
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $user = User::factory()->create()->assignRole('admin');
        
        $this->actingAs($user);

        $response = $this->post(route('meetings.store-multiple'), []);

        $response->assertSessionHasErrors([
            'local_committee_id',
            'meetings'
        ]);
    }

    #[Test]
    public function it_validates_meeting_data()
    {
        $user = User::factory()->create()->assignRole('admin');
        $committee = LocalCommittee::factory()->create();
        
        $this->actingAs($user);

        $meetingsData = [
            'local_committee_id' => $committee->id,
            'meetings' => [
                [
                    'title' => '', // Titre vide
                    'scheduled_date' => 'invalid-date', // Date invalide
                    'scheduled_time' => '', // Heure vide
                    'location' => '' // Lieu vide
                ]
            ]
        ];

        $response = $this->post(route('meetings.store-multiple'), $meetingsData);

        $response->assertSessionHasErrors([
            'meetings.0.title',
            'meetings.0.scheduled_date',
            'meetings.0.scheduled_time',
            'meetings.0.location'
        ]);
    }
} 
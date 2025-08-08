<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Meeting;
use App\Models\User;
use App\Models\LocalCommittee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class MeetingSubmissionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $meeting;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur
        $this->user = User::factory()->create();
        
        // Créer un comité local
        $localCommittee = LocalCommittee::factory()->create();
        
        // Créer une réunion
        $this->meeting = Meeting::factory()->create([
            'local_committee_id' => $localCommittee->id,
            'status' => 'scheduled',
            'attendance_status' => 'draft',
        ]);
    }

    /** @test */
    public function it_can_submit_complete_meeting_from_mobile()
    {
        $this->actingAs($this->user);

        $data = [
            'meeting' => [
                'title' => 'Réunion test',
                'description' => 'Description de test',
                'agenda' => 'Ordre du jour de test',
                'difficulties' => 'Difficultés rencontrées',
                'recommendations' => 'Recommandations',
            ],
            'attendances' => [
                [
                    'name' => 'John Doe',
                    'phone' => '123456789',
                    'role' => 'Président',
                    'is_expected' => true,
                    'is_present' => true,
                    'comments' => 'Présent',
                    'attendance_status' => 'present',
                ],
                [
                    'name' => 'Jane Smith',
                    'phone' => '987654321',
                    'role' => 'Secrétaire',
                    'is_expected' => true,
                    'is_present' => false,
                    'comments' => 'Absent',
                    'attendance_status' => 'absent',
                ],
            ],
            'minutes' => [
                'content' => 'Compte-rendu de la réunion',
                'status' => 'draft',
                'difficulties' => 'Difficultés rencontrées',
                'recommendations' => 'Recommandations',
                'people_to_enroll_count' => 10,
                'people_enrolled_count' => 8,
                'cmu_cards_available_count' => 50,
                'cmu_cards_distributed_count' => 45,
                'complaints_received_count' => 5,
                'complaints_processed_count' => 4,
            ],
            'attachments' => [
                [
                    'title' => 'Document de test',
                    'original_name' => 'test.pdf',
                    'file_path' => 'attachments/test.pdf',
                    'file_type' => 'application/pdf',
                    'nature' => 'document_justificatif',
                    'size' => 1024,
                ],
            ],
            'submitted_at' => now()->toISOString(),
            'submitted_by' => $this->user->id,
        ];

        $response = $this->postJson("/api/meetings/{$this->meeting->id}/submit-complete", $data);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Réunion soumise avec succès. Toutes les données ont été enregistrées.',
                ]);

        // Vérifier que la réunion a été mise à jour
        $this->meeting->refresh();
        $this->assertEquals('submitted', $this->meeting->attendance_status);
        $this->assertNotNull($this->meeting->attendance_submitted_at);
        $this->assertEquals($this->user->id, $this->meeting->attendance_submitted_by);

        // Vérifier que les attendances ont été créées
        $this->assertCount(2, $this->meeting->attendees);
        
        // Vérifier que les minutes ont été créées
        $this->assertNotNull($this->meeting->minutes);
        $this->assertEquals('Compte-rendu de la réunion', $this->meeting->minutes->content);
        
        // Vérifier que les attachments ont été créés
        $this->assertCount(1, $this->meeting->attachments);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/meetings/{$this->meeting->id}/submit-complete", []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['meeting']);
    }

    /** @test */
    public function it_handles_invalid_meeting_id()
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/meetings/99999/submit-complete", [
            'meeting' => ['title' => 'Test'],
        ]);

        $response->assertStatus(404);
    }
} 
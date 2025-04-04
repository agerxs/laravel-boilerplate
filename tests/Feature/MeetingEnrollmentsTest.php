<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Feature\Traits\WithRoles;

class MeetingEnrollmentsTest extends TestCase
{
    use RefreshDatabase, WithRoles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRoles();
    }

    public function test_secretaire_peut_mettre_a_jour_enrollements_reunion_non_validee()
    {
        // Créer un utilisateur avec le rôle secrétaire
        $secretaire = User::factory()->create();
        $secretaire->assignRole('secretaire');

        // Créer une réunion non validée
        $meeting = Meeting::factory()->create([
            'status' => 'scheduled',
            'target_enrollments' => 10,
            'actual_enrollments' => 5
        ]);

        // Simuler la requête de mise à jour
        $response = $this->actingAs($secretaire)
            ->patch(route('meetings.update-enrollments', $meeting), [
                'target_enrollments' => 15,
                'actual_enrollments' => 8
            ]);

        // Vérifier que la mise à jour a réussi
        $response->assertStatus(200);
        $this->assertEquals(15, $meeting->fresh()->target_enrollments);
        $this->assertEquals(8, $meeting->fresh()->actual_enrollments);
    }

    public function test_admin_peut_mettre_a_jour_enrollements_reunion_validee()
    {
        // Créer un utilisateur avec le rôle admin
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Créer une réunion validée
        $meeting = Meeting::factory()->create([
            'status' => 'validated',
            'target_enrollments' => 10,
            'actual_enrollments' => 5
        ]);

        // Simuler la requête de mise à jour
        $response = $this->actingAs($admin)
            ->patch(route('meetings.update-enrollments', $meeting), [
                'target_enrollments' => 20,
                'actual_enrollments' => 15
            ]);

        // Vérifier que la mise à jour a réussi
        $response->assertStatus(200);
        $this->assertEquals(20, $meeting->fresh()->target_enrollments);
        $this->assertEquals(15, $meeting->fresh()->actual_enrollments);
    }

    public function test_secretaire_ne_peut_pas_mettre_a_jour_enrollements_reunion_validee()
    {
        // Créer un utilisateur avec le rôle secrétaire
        $secretaire = User::factory()->create();
        $secretaire->assignRole('secretaire');

        // Créer une réunion validée
        $meeting = Meeting::factory()->create([
            'status' => 'validated',
            'target_enrollments' => 10,
            'actual_enrollments' => 5
        ]);

        // Simuler la requête de mise à jour
        $response = $this->actingAs($secretaire)
            ->patch(route('meetings.update-enrollments', $meeting), [
                'target_enrollments' => 15,
                'actual_enrollments' => 8
            ]);

        // Vérifier que la mise à jour a été refusée
        $response->assertStatus(403);
        $this->assertEquals(10, $meeting->fresh()->target_enrollments);
        $this->assertEquals(5, $meeting->fresh()->actual_enrollments);
    }

    public function test_utilisateur_non_autorise_ne_peut_pas_mettre_a_jour_enrollements()
    {
        // Créer un utilisateur sans rôle spécial
        $user = User::factory()->create();

        // Créer une réunion
        $meeting = Meeting::factory()->create([
            'status' => 'scheduled',
            'target_enrollments' => 10,
            'actual_enrollments' => 5
        ]);

        // Simuler la requête de mise à jour
        $response = $this->actingAs($user)
            ->patch(route('meetings.update-enrollments', $meeting), [
                'target_enrollments' => 15,
                'actual_enrollments' => 8
            ]);

        // Vérifier que la mise à jour a été refusée
        $response->assertStatus(403);
        $this->assertEquals(10, $meeting->fresh()->target_enrollments);
        $this->assertEquals(5, $meeting->fresh()->actual_enrollments);
    }

    public function test_validation_des_donnees_enrollements()
    {
        // Créer un utilisateur admin
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Créer une réunion
        $meeting = Meeting::factory()->create([
            'target_enrollments' => 10,
            'actual_enrollments' => 5
        ]);

        // Tester avec des valeurs invalides
        $response = $this->actingAs($admin)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest'
            ])
            ->patch(route('meetings.update-enrollments', $meeting), [
                'target_enrollments' => -1, // Invalide : nombre négatif
                'actual_enrollments' => 20  // Invalide : supérieur à target_enrollments
            ]);

        // Vérifier que la validation a échoué
        $response->assertStatus(422);
        
        // Vérifier que les valeurs n'ont pas changé
        $this->assertEquals(10, $meeting->fresh()->target_enrollments);
        $this->assertEquals(5, $meeting->fresh()->actual_enrollments);
    }

    public function test_secretaire_peut_mettre_a_jour_enrollements_reunion_terminee()
    {
        // Créer un utilisateur avec le rôle secrétaire
        $secretaire = User::factory()->create();
        $secretaire->assignRole('secretaire');

        // Créer une réunion terminée
        $meeting = Meeting::factory()->create([
            'status' => 'completed',
            'target_enrollments' => 10,
            'actual_enrollments' => 5
        ]);

        // Simuler la requête de mise à jour
        $response = $this->actingAs($secretaire)
            ->patch(route('meetings.update-enrollments', $meeting), [
                'target_enrollments' => 15,
                'actual_enrollments' => 8
            ]);

        // Vérifier que la mise à jour a réussi
        $response->assertStatus(200);
        $this->assertEquals(15, $meeting->fresh()->target_enrollments);
        $this->assertEquals(8, $meeting->fresh()->actual_enrollments);
    }
} 
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Meeting;
use App\Models\LocalCommittee;
use App\Models\PaymentRate;
use App\Models\MeetingPaymentList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class MeetingManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authorized_user_can_create_meeting()
    {
        $user = User::factory()->create()->assignRole('admin');
        $committee = LocalCommittee::factory()->create();
        
        $this->actingAs($user);

        $response = $this->withSession(['_token' => 'test-token'])
            ->post(route('meetings.store'), [
                'title' => 'Réunion test',
                'local_committee_id' => $committee->id,
                'scheduled_date' => now()->addDays(7)->format('Y-m-d'),
                'scheduled_time' => '14:00',
                'location' => 'Salle de réunion',
                'description' => 'Description de la réunion',
                'target_enrollments' => 100,
                'actual_enrollments' => 75,
                '_token' => 'test-token'
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('meetings', [
            'title' => 'Réunion test',
            'local_committee_id' => $committee->id,
            'target_enrollments' => 100,
            'actual_enrollments' => 75
        ]);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_validates_enrollment_numbers()
    {
        $user = User::factory()->create()->assignRole('admin');
        $committee = LocalCommittee::factory()->create();
        
        $this->actingAs($user);

        $response = $this->withSession(['_token' => 'test-token'])
            ->post(route('meetings.store'), [
                'title' => 'Réunion test',
                'local_committee_id' => $committee->id,
                'scheduled_date' => now()->addDays(7)->format('Y-m-d'),
                'scheduled_time' => '14:00',
                'location' => 'Salle de réunion',
                'description' => 'Description de la réunion',
                'target_enrollments' => -1,
                'actual_enrollments' => 'abc',
                '_token' => 'test-token'
            ]);

        $response->assertSessionHasErrors([
            'target_enrollments',
            'actual_enrollments'
        ]);
    }

    #[Test]
    public function actual_enrollments_cannot_exceed_target_enrollments()
    {
        $user = User::factory()->create()->assignRole('admin');
        $committee = LocalCommittee::factory()->create();
        
        $this->actingAs($user);

        $response = $this->withSession(['_token' => 'test-token'])
            ->post(route('meetings.store'), [
                'title' => 'Réunion test',
                'local_committee_id' => $committee->id,
                'scheduled_date' => now()->addDays(7)->format('Y-m-d'),
                'scheduled_time' => '14:00',
                'location' => 'Salle de réunion',
                'description' => 'Description de la réunion',
                'target_enrollments' => 100,
                'actual_enrollments' => 150,
                '_token' => 'test-token'
            ]);

        $response->assertSessionHasErrors('actual_enrollments');
    }

    /**
     * Test qu'un sous-préfet peut valider une réunion marquée comme terminée.
     */
    #[Test]
    public function test_sous_prefet_peut_valider_reunion_terminee()
    {
        // Créer un sous-préfet
        $sousPrefet = User::factory()->create();
        $sousPrefet->assignRole('sous-prefet');

        // Créer une réunion marquée comme terminée
        $meeting = Meeting::factory()->create([
            'status' => 'completed'
        ]);

        // Créer une liste de paiement en brouillon
        $paymentList = MeetingPaymentList::factory()->create([
            'meeting_id' => $meeting->id,
            'status' => 'draft'
        ]);

        // Tenter de valider la réunion
        $response = $this->actingAs($sousPrefet)
            ->postJson(route('meetings.validate', $meeting->id));

        // Vérifier que la réponse est réussie
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Réunion validée avec succès et liste de paiement soumise.'
        ]);

        // Vérifier que le statut de la réunion a été mis à jour
        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'status' => 'validated',
            'validated_by' => $sousPrefet->id
        ]);

        // Vérifier que la liste de paiement a été soumise
        $this->assertDatabaseHas('meeting_payment_lists', [
            'id' => $paymentList->id,
            'status' => 'submitted'
        ]);
    }

    /**
     * Test qu'un sous-préfet ne peut pas valider une réunion qui n'est pas terminée.
     */
    #[Test]
    public function test_sous_prefet_ne_peut_pas_valider_reunion_non_terminee()
    {
        // Créer un sous-préfet
        $sousPrefet = User::factory()->create();
        $sousPrefet->assignRole('sous-prefet');

        // Créer une réunion avec un statut différent de 'completed'
        $meeting = Meeting::factory()->create([
            'status' => 'scheduled'
        ]);

        // Tenter de valider la réunion
        $response = $this->actingAs($sousPrefet)
            ->postJson(route('meetings.validate', $meeting->id));

        // Vérifier que la réponse est une erreur 400
        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Cette réunion ne peut pas être validée car elle n\'est pas marquée comme terminée.'
        ]);

        // Vérifier que le statut de la réunion n'a pas été modifié
        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'status' => 'scheduled'
        ]);
    }

    #[Test]
    public function gestionnaire_peut_valider_liste_paiement()
    {
        // Créer un gestionnaire
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole('gestionnaire');

        // Créer une réunion terminée
        $meeting = Meeting::factory()->create([
            'status' => 'completed'
        ]);

        // Créer une liste de paiement soumise
        $paymentList = MeetingPaymentList::factory()->create([
            'meeting_id' => $meeting->id,
            'status' => 'submitted'
        ]);

        // Valider la liste de paiement
        $response = $this->actingAs($gestionnaire)
            ->postJson(route('meeting-payments.lists.validate', $paymentList->id));

        // Vérifier que la réponse est réussie
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Liste de paiement validée avec succès.'
            ]);

        // Vérifier que le statut de la liste a été mis à jour
        $this->assertDatabaseHas('meeting_payment_lists', [
            'id' => $paymentList->id,
            'status' => 'validated',
            'validated_by' => $gestionnaire->id
        ]);
    }

    #[Test]
    public function gestionnaire_peut_rejeter_liste_paiement()
    {
        // Créer un gestionnaire
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole('gestionnaire');

        // Créer une réunion terminée
        $meeting = Meeting::factory()->create([
            'status' => 'completed'
        ]);

        // Créer une liste de paiement soumise
        $paymentList = MeetingPaymentList::factory()->create([
            'meeting_id' => $meeting->id,
            'status' => 'submitted'
        ]);

        // Rejeter la liste de paiement
        $response = $this->actingAs($gestionnaire)
            ->postJson(route('meeting-payments.lists.reject', $paymentList->id), [
                'rejection_reason' => 'Données incomplètes'
            ]);

        // Vérifier que la réponse est réussie
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Liste de paiement rejetée avec succès.'
            ]);

        // Vérifier que le statut de la liste a été mis à jour
        $this->assertDatabaseHas('meeting_payment_lists', [
            'id' => $paymentList->id,
            'status' => 'rejected',
            'rejected_by' => $gestionnaire->id,
            'rejection_reason' => 'Données incomplètes'
        ]);
    }
} 
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Meeting;
use App\Models\MeetingPaymentList;
use App\Models\MeetingPaymentItem;
use App\Models\MeetingAttendee;
use App\Models\LocalCommittee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;

class MeetingPaymentListControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $tresorier;
    protected $sousPrefet;
    protected $secretaire;
    protected $meeting;
    protected $localCommittee;
    protected $paymentList;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer le comité local
        $this->localCommittee = LocalCommittee::factory()->create();

        // Créer les utilisateurs avec leurs rôles
        $this->tresorier = User::factory()->create();
        $this->tresorier->assignRole('tresorier');

        $this->secretaire = User::factory()->create();
        $this->secretaire->assignRole('secretaire');

        // Créer une réunion
        $this->meeting = Meeting::factory()->create([
            'local_committee_id' => $this->localCommittee->id,
            'status' => 'completed'
        ]);

        // Créer une liste de paiement
        $this->paymentList = MeetingPaymentList::factory()->create([
            'meeting_id' => $this->meeting->id,
            'status' => 'submitted'
        ]);
    }

    /** @test */
    public function gestionnaire_can_see_payment_lists()
    {
        $this->actingAs($this->tresorier);

        $response = $this->get(route('meeting-payments.lists.index'));

        $response->assertStatus(200)
            ->assertInertia(fn ($assert) => $assert
                ->component('MeetingPayments/Lists/Index')
                ->has('paymentLists')
                ->has('meetings')
                ->has('localCommittees')
                ->where('canValidate', true)
            );
    }

    /** @test */
    public function gestionnaire_can_validate_payment_item()
    {
        $this->actingAs($this->tresorier);
        $paymentList = MeetingPaymentList::factory()->create([
            'meeting_id' => $this->meeting->id,
            'status' => 'submitted'
        ]);
        $paymentItem = MeetingPaymentItem::factory()->create([
            'meeting_payment_list_id' => $paymentList->id,
            'payment_status' => 'pending'
        ]);

        $response = $this->post(route('meeting-payments.lists.validate-item', $paymentItem->id));

        $response->assertStatus(200);
        $this->assertEquals('validated', $paymentItem->fresh()->payment_status);
    }

    /** @test */
    public function gestionnaire_can_validate_all_payments()
    {
        $this->actingAs($this->tresorier);

        // Créer plusieurs éléments de paiement
        $attendees = MeetingAttendee::factory()->count(3)->create([
            'meeting_id' => $this->meeting->id,
            'attendance_status' => 'present'
        ]);

        foreach ($attendees as $attendee) {
            MeetingPaymentItem::factory()->create([
                'meeting_payment_list_id' => $this->paymentList->id,
                'attendee_id' => $attendee->id,
                'payment_status' => 'pending',
                'amount' => MeetingPaymentList::PARTICIPANT_AMOUNT
            ]);
        }

        $response = $this->post(route('meeting-payments.lists.validate-all'), [
            'meeting_id' => $this->meeting->id
        ]);

        $response->assertStatus(200);
        $this->assertEquals(0, MeetingPaymentItem::where('payment_status', 'pending')->count());
        $this->assertEquals(3, MeetingPaymentItem::where('payment_status', 'validated')->count());
    }

    /** @test */
    public function non_gestionnaire_cannot_validate_payments()
    {
        $this->actingAs($this->secretaire);

        $attendee = MeetingAttendee::factory()->create([
            'meeting_id' => $this->meeting->id,
            'attendance_status' => 'present'
        ]);

        $paymentItem = MeetingPaymentItem::factory()->create([
            'meeting_payment_list_id' => $this->paymentList->id,
            'attendee_id' => $attendee->id,
            'payment_status' => 'pending'
        ]);

        $response = $this->post(route('meeting-payments.lists.validate-item', $paymentItem->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function payment_list_filters_work_correctly()
    {
        $this->actingAs($this->tresorier);

        // Créer une autre réunion et liste de paiement
        $otherMeeting = Meeting::factory()->create([
            'local_committee_id' => $this->localCommittee->id,
            'status' => 'completed'
        ]);

        $otherPaymentList = MeetingPaymentList::factory()->create([
            'meeting_id' => $otherMeeting->id,
            'submitted_by' => $this->secretaire->id,
            'status' => 'validated'
        ]);

        // Test du filtre par statut
        $response = $this->get(route('meeting-payments.lists.index', ['status' => 'validated']));
        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => $assert
            ->component('MeetingPayments/Lists/Index')
            ->where('paymentLists.data.0.id', $otherPaymentList->id)
        );

        // Test du filtre par réunion
        $response = $this->get(route('meeting-payments.lists.index', ['meeting_id' => $this->meeting->id]));
        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => $assert
            ->component('MeetingPayments/Lists/Index')
            ->where('paymentLists.data.0.id', $this->paymentList->id)
        );
    }

    /** @test */
    public function payment_amounts_are_calculated_correctly()
    {
        $this->actingAs($this->tresorier);

        $attendees = [
            ['role' => 'sous_prefet', 'amount' => MeetingPaymentList::SUB_PREFET_AMOUNT],
            ['role' => 'secretaire', 'amount' => MeetingPaymentList::SECRETARY_AMOUNT],
            ['role' => 'participant', 'amount' => MeetingPaymentList::PARTICIPANT_AMOUNT],
        ];

        foreach ($attendees as $attendeeData) {
            $attendee = MeetingAttendee::factory()->create([
                'meeting_id' => $this->meeting->id,
                'attendance_status' => 'present'
            ]);

            MeetingPaymentItem::factory()->create([
                'meeting_payment_list_id' => $this->paymentList->id,
                'attendee_id' => $attendee->id,
                'role' => $attendeeData['role'],
                'amount' => $attendeeData['amount'],
                'payment_status' => 'pending'
            ]);
        }

        $response = $this->get(route('meeting-payments.lists.show', $this->paymentList->id));
        $response->assertStatus(200);

        $totalAmount = MeetingPaymentList::SUB_PREFET_AMOUNT +
            MeetingPaymentList::SECRETARY_AMOUNT +
            MeetingPaymentList::PARTICIPANT_AMOUNT;

        $this->assertEquals($totalAmount, $this->paymentList->fresh()->paymentItems->sum('amount'));
    }

    /** @test */
    public function gestionnaire_can_export_payment_lists()
    {
        $this->actingAs($this->tresorier);

        // Créer une liste de paiement validée
        $validatedPaymentList = MeetingPaymentList::factory()->create([
            'meeting_id' => $this->meeting->id,
            'submitted_by' => $this->secretaire->id,
            'status' => 'validated'
        ]);

        // Créer des éléments de paiement
        $attendees = MeetingAttendee::factory()->count(2)->create([
            'meeting_id' => $this->meeting->id,
            'attendance_status' => 'present'
        ]);

        foreach ($attendees as $attendee) {
            MeetingPaymentItem::factory()->create([
                'meeting_payment_list_id' => $validatedPaymentList->id,
                'attendee_id' => $attendee->id,
                'payment_status' => 'validated',
                'amount' => MeetingPaymentList::PARTICIPANT_AMOUNT
            ]);
        }

        $response = $this->get(route('meeting-payments.lists.export'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'Réunion',
                        'Date',
                        'Comité Local',
                        'Participants' => [
                            '*' => [
                                'Nom',
                                'Rôle',
                                'Montant',
                                'Statut'
                            ]
                        ]
                    ]
                ],
                'total_amount'
            ]);

        // Vérifier que seules les listes validées sont exportées
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($validatedPaymentList->meeting->title, $response->json('data.0.Réunion'));
        $this->assertEquals(2, count($response->json('data.0.Participants')));
    }

    /** @test */
    public function non_gestionnaire_cannot_export_payment_lists()
    {
        $this->actingAs($this->secretaire);

        $response = $this->get(route('meeting-payments.lists.export'));

        $response->assertStatus(403);
    }

    /** @test */
    public function gestionnaire_can_validate_payment_list()
    {
        $paymentList = MeetingPaymentList::factory()->create([
            'meeting_id' => $this->meeting->id,
            'status' => 'submitted'
        ]);

        $response = $this->actingAs($this->tresorier)
            ->postJson(route('meeting-payments.lists.validate', $paymentList->id));

        $response->assertStatus(200)
            ->assertJson(['message' => 'Liste de paiement validée avec succès.']);

        $this->assertEquals('validated', $paymentList->fresh()->status);
        $this->assertEquals($this->tresorier->id, $paymentList->fresh()->validated_by);
    }

    /** @test */
    public function gestionnaire_can_reject_payment_list()
    {
        $paymentList = MeetingPaymentList::factory()->create([
            'meeting_id' => $this->meeting->id,
            'status' => 'submitted'
        ]);

        $response = $this->actingAs($this->tresorier)
            ->postJson(route('meeting-payments.lists.reject', $paymentList->id), [
                'rejection_reason' => 'Données incomplètes'
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Liste de paiement rejetée avec succès.']);

        $this->assertEquals('rejected', $paymentList->fresh()->status);
        $this->assertEquals('Données incomplètes', $paymentList->fresh()->rejection_reason);
    }

    /** @test */
    public function non_gestionnaire_cannot_validate_payment_list()
    {
        $paymentList = MeetingPaymentList::factory()->create([
            'meeting_id' => $this->meeting->id,
            'status' => 'submitted'
        ]);

        $response = $this->actingAs($this->secretaire)
            ->postJson(route('meeting-payments.lists.validate', $paymentList->id));

        $response->assertStatus(403);
        $this->assertEquals('submitted', $paymentList->fresh()->status);
    }

    /** @test */
    public function non_gestionnaire_cannot_reject_payment_list()
    {
        $paymentList = MeetingPaymentList::factory()->create([
            'meeting_id' => $this->meeting->id,
            'status' => 'submitted'
        ]);

        $response = $this->actingAs($this->secretaire)
            ->postJson(route('meeting-payments.lists.reject', $paymentList->id), [
                'rejection_reason' => 'Données incomplètes'
            ]);

        $response->assertStatus(403);
        $this->assertEquals('submitted', $paymentList->fresh()->status);
    }

    /** @test */
    public function payment_list_cannot_be_validated_if_not_submitted()
    {
        $paymentList = MeetingPaymentList::factory()->create([
            'meeting_id' => $this->meeting->id,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($this->tresorier)
            ->postJson(route('meeting-payments.lists.validate', $paymentList->id));

        $response->assertStatus(400);
        $this->assertEquals('draft', $paymentList->fresh()->status);
    }

    /** @test */
    public function payment_list_cannot_be_rejected_if_not_submitted()
    {
        $paymentList = MeetingPaymentList::factory()->create([
            'meeting_id' => $this->meeting->id,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($this->tresorier)
            ->postJson(route('meeting-payments.lists.reject', $paymentList->id), [
                'rejection_reason' => 'Données incomplètes'
            ]);

        $response->assertStatus(400);
        $this->assertEquals('draft', $paymentList->fresh()->status);
    }
}

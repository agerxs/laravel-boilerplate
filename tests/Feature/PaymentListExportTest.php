<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Meeting;
use App\Models\User;
use App\Models\LocalCommittee;
use App\Models\Locality;
use App\Models\MeetingAttendee;
use App\Models\MeetingPaymentList;
use App\Models\MeetingPaymentItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class PaymentListExportTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $localCommittee;
    protected $meeting;
    protected $paymentList;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un gestionnaire
        $this->user = User::factory()->create();
        $this->user->assignRole('gestionnaire');
        
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
        
        // Créer un comité local
        $this->localCommittee = LocalCommittee::factory()->create([
            'locality_id' => $locality->id,
        ]);
        
        // Créer une réunion
        $this->meeting = Meeting::factory()->create([
            'local_committee_id' => $this->localCommittee->id,
            'status' => 'completed',
            'created_by' => $this->user->id,
        ]);
        
        // Créer des participants
        $attendee1 = MeetingAttendee::create([
            'meeting_id' => $this->meeting->id,
            'localite_id' => $village->id,
            'name' => 'Participant 1',
            'phone' => '123456789',
            'role' => 'participant',
            'attendance_status' => 'present',
            'is_expected' => true,
            'is_present' => true,
        ]);

        $attendee2 = MeetingAttendee::create([
            'meeting_id' => $this->meeting->id,
            'localite_id' => $village->id,
            'name' => 'Participant 2',
            'phone' => '987654321',
            'role' => 'participant',
            'attendance_status' => 'present',
            'is_expected' => true,
            'is_present' => true,
        ]);
        
        // Créer une liste de paiement
        $this->paymentList = MeetingPaymentList::create([
            'meeting_id' => $this->meeting->id,
            'submitted_at' => now(),
            'status' => 'submitted',
            'submitted_by' => $this->user->id,
            'total_amount' => 2000,
        ]);

        // Créer des éléments de paiement
        MeetingPaymentItem::create([
            'meeting_payment_list_id' => $this->paymentList->id,
            'attendee_id' => $attendee1->id,
            'amount' => 1000,
            'role' => 'participant',
            'payment_status' => 'pending',
            'name' => 'Participant 1',
            'phone' => '123456789'
        ]);

        MeetingPaymentItem::create([
            'meeting_payment_list_id' => $this->paymentList->id,
            'attendee_id' => $attendee2->id,
            'amount' => 1000,
            'role' => 'participant',
            'payment_status' => 'pending',
            'name' => 'Participant 2',
            'phone' => '987654321'
        ]);
    }

    /** @test */
    public function it_can_export_payment_lists()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/meeting-payments/lists/export');

        $response->assertStatus(200);
        
        $data = $response->json();
        
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('total_amount', $data);
        $this->assertArrayHasKey('total_items', $data);
        $this->assertCount(2, $data['data']); // 2 participants
        
        $firstItem = $data['data'][0];
        $this->assertArrayHasKey('Référence', $firstItem);
        $this->assertArrayHasKey('Montant', $firstItem);
        $this->assertArrayHasKey('Nom du Destinataire', $firstItem);
        $this->assertArrayHasKey('Commentaire', $firstItem);
        $this->assertArrayHasKey('Type d\'opération', $firstItem);
        $this->assertEquals('transfert-mobile-money', $firstItem['Type d\'opération']);
        $this->assertEquals(1000, $firstItem['Montant']);
    }

    /** @test */
    public function it_can_export_single_meeting_payment_list()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/meeting-payments/lists/export-single/{$this->meeting->id}");

        $response->assertStatus(200);
        
        $data = $response->json();
        
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('total_amount', $data);
        $this->assertArrayHasKey('meeting_title', $data);
        $this->assertArrayHasKey('total_items', $data);
        
        $mobileMoneyData = $data['data'];
        $this->assertCount(2, $mobileMoneyData); // 2 participants
        
        $firstItem = $mobileMoneyData[0];
        $this->assertArrayHasKey('Référence', $firstItem);
        $this->assertArrayHasKey('Montant', $firstItem);
        $this->assertArrayHasKey('Nom du Destinataire', $firstItem);
        $this->assertArrayHasKey('Commentaire', $firstItem);
        $this->assertArrayHasKey('Type d\'opération', $firstItem);
        $this->assertEquals('transfert-mobile-money', $firstItem['Type d\'opération']);
    }

    /** @test */
    public function it_handles_payment_items_without_attendee()
    {
        // Créer un élément de paiement sans attendee
        MeetingPaymentItem::create([
            'meeting_payment_list_id' => $this->paymentList->id,
            'attendee_id' => null,
            'amount' => 500,
            'role' => 'participant',
            'payment_status' => 'pending',
            'name' => 'Participant sans attendee',
            'phone' => '555555555'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/meeting-payments/lists/export');

        $response->assertStatus(200);
        
        $data = $response->json();
        
        // Vérifier que le participant sans attendee est bien exporté
        $mobileMoneyData = $data['data'];
        $participantWithoutAttendee = collect($mobileMoneyData)->first(function($item) {
            return $item['Nom du Destinataire'] === 'Participant sans attendee';
        });
        
        $this->assertNotNull($participantWithoutAttendee);
        $this->assertEquals(500, $participantWithoutAttendee['Montant']);
        $this->assertEquals('555555555', $participantWithoutAttendee['Référence']);
    }

    /** @test */
    public function it_filters_payment_lists_by_local_committee()
    {
        // Créer un autre comité local et réunion
        $otherLocality = Locality::factory()->create(['name' => 'Autre Sous-préfecture']);
        $otherCommittee = LocalCommittee::factory()->create(['locality_id' => $otherLocality->id]);
        $otherMeeting = Meeting::factory()->create([
            'local_committee_id' => $otherCommittee->id,
            'status' => 'completed',
        ]);
        
        $otherPaymentList = MeetingPaymentList::create([
            'meeting_id' => $otherMeeting->id,
            'submitted_at' => now(),
            'status' => 'submitted',
            'submitted_by' => $this->user->id,
            'total_amount' => 1000,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/meeting-payments/lists/export?local_committee_id={$this->localCommittee->id}");

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertCount(1, $data['data']);
        $this->assertEquals($this->meeting->title, $data['data'][0]['Réunion']);
    }

    /** @test */
    public function it_filters_payment_lists_by_status()
    {
        // Créer une liste avec un statut différent
        $draftPaymentList = MeetingPaymentList::create([
            'meeting_id' => $this->meeting->id,
            'submitted_at' => now(),
            'status' => 'draft',
            'submitted_by' => $this->user->id,
            'total_amount' => 1000,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/meeting-payments/lists/export?status=submitted');

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertCount(1, $data['data']);
        $this->assertEquals('Soumis', $data['data'][0]['Statut Liste']);
    }

    /** @test */
    public function it_returns_404_for_non_existent_meeting_export()
    {
        $nonExistentMeetingId = 99999;
        
        $response = $this->actingAs($this->user)
            ->getJson("/meeting-payments/lists/export-single/{$nonExistentMeetingId}");

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Aucune liste de paiement trouvée pour cette réunion']);
    }

    /** @test */
    public function it_requires_authorized_user_for_export()
    {
        // Créer un utilisateur sans les droits
        $unauthorizedUser = User::factory()->create();
        $unauthorizedUser->assignRole('secretaire');

        $response = $this->actingAs($unauthorizedUser)
            ->getJson('/meeting-payments/lists/export');

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Accès non autorisé']);
    }
} 
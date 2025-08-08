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

class AttendanceValidationPaymentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $localCommittee;
    protected $meeting;
    protected $attendees;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un sous-préfet
        $this->user = User::factory()->create();
        $this->user->assignRole('sous-prefet');
        
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
            'status' => 'planned',
            'attendance_status' => 'submitted',
            'created_by' => $this->user->id,
        ]);
        
        // Créer des participants présents
        $this->attendees = [
            MeetingAttendee::create([
                'meeting_id' => $this->meeting->id,
                'localite_id' => $village->id,
                'name' => 'Participant 1',
                'phone' => '123456789',
                'role' => 'participant',
                'attendance_status' => 'present',
                'is_expected' => true,
                'is_present' => true,
            ]),
            MeetingAttendee::create([
                'meeting_id' => $this->meeting->id,
                'localite_id' => $village->id,
                'name' => 'Participant 2',
                'phone' => '987654321',
                'role' => 'participant',
                'attendance_status' => 'replaced',
                'is_expected' => true,
                'is_present' => true,
            ]),
        ];
    }

    /** @test */
    public function it_generates_payment_list_when_validating_attendance()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/meetings/{$this->meeting->id}/validate-attendance");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Présences validées avec succès et liste de paiement générée.'
        ]);

        // Vérifier que la liste de paiement a été créée
        $this->assertDatabaseHas('meeting_payment_lists', [
            'meeting_id' => $this->meeting->id,
            'status' => 'submitted',
            'submitted_by' => $this->user->id,
        ]);

        // Vérifier que les éléments de paiement ont été créés pour les participants présents
        $paymentList = MeetingPaymentList::where('meeting_id', $this->meeting->id)->first();
        $this->assertNotNull($paymentList);

        $paymentItems = $paymentList->paymentItems;
        $this->assertCount(2, $paymentItems); // 2 participants présents

        // Vérifier que les montants sont corrects
        foreach ($paymentItems as $item) {
            $this->assertEquals(MeetingPaymentList::PARTICIPANT_AMOUNT, $item->amount);
            $this->assertEquals('pending', $item->payment_status);
        }
    }

    /** @test */
    public function it_does_not_generate_payment_list_if_already_exists()
    {
        // Créer une liste de paiement existante
        $existingPaymentList = MeetingPaymentList::create([
            'meeting_id' => $this->meeting->id,
            'submitted_at' => now(),
            'status' => 'submitted',
            'submitted_by' => $this->user->id,
            'total_amount' => 1000,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/meetings/{$this->meeting->id}/validate-attendance");

        $response->assertStatus(200);

        // Vérifier qu'aucune nouvelle liste de paiement n'a été créée
        $paymentLists = MeetingPaymentList::where('meeting_id', $this->meeting->id)->get();
        $this->assertCount(1, $paymentLists);
        $this->assertEquals($existingPaymentList->id, $paymentLists->first()->id);
    }

    /** @test */
    public function it_does_not_generate_payment_list_without_present_attendees()
    {
        // Marquer tous les participants comme absents
        foreach ($this->attendees as $attendee) {
            $attendee->update([
                'attendance_status' => 'absent',
                'is_present' => false,
            ]);
        }

        $response = $this->actingAs($this->user)
            ->postJson("/meetings/{$this->meeting->id}/validate-attendance");

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Impossible de valider les présences : aucun participant présent.'
        ]);

        // Vérifier qu'aucune liste de paiement n'a été créée
        $this->assertDatabaseMissing('meeting_payment_lists', [
            'meeting_id' => $this->meeting->id,
        ]);
    }

    /** @test */
    public function it_generates_payment_list_with_correct_amounts_for_different_roles()
    {
        // Créer des participants avec différents rôles
        MeetingAttendee::create([
            'meeting_id' => $this->meeting->id,
            'localite_id' => 1,
            'name' => 'Sous-préfet',
            'phone' => '111111111',
            'role' => 'sous-prefet',
            'attendance_status' => 'present',
            'is_expected' => true,
            'is_present' => true,
        ]);

        MeetingAttendee::create([
            'meeting_id' => $this->meeting->id,
            'localite_id' => 1,
            'name' => 'Secrétaire',
            'phone' => '222222222',
            'role' => 'secretaire',
            'attendance_status' => 'present',
            'is_expected' => true,
            'is_present' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/meetings/{$this->meeting->id}/validate-attendance");

        $response->assertStatus(200);

        // Vérifier que les éléments de paiement ont les bons montants
        $paymentList = MeetingPaymentList::where('meeting_id', $this->meeting->id)->first();
        $paymentItems = $paymentList->paymentItems;

        $sousPrefetItem = $paymentItems->where('role', 'sous-prefet')->first();
        $this->assertNotNull($sousPrefetItem);
        $this->assertEquals(MeetingPaymentList::SUB_PREFET_AMOUNT, $sousPrefetItem->amount);

        $secretaireItem = $paymentItems->where('role', 'secretaire')->first();
        $this->assertNotNull($secretaireItem);
        $this->assertEquals(MeetingPaymentList::SECRETARY_AMOUNT, $secretaireItem->amount);

        $participantItems = $paymentItems->where('role', 'participant');
        foreach ($participantItems as $item) {
            $this->assertEquals(MeetingPaymentList::PARTICIPANT_AMOUNT, $item->amount);
        }
    }

    /** @test */
    public function it_calculates_total_amount_correctly()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/meetings/{$this->meeting->id}/validate-attendance");

        $response->assertStatus(200);

        $paymentList = MeetingPaymentList::where('meeting_id', $this->meeting->id)->first();
        $expectedTotal = MeetingPaymentList::PARTICIPANT_AMOUNT * 2; // 2 participants

        $this->assertEquals($expectedTotal, $paymentList->total_amount);
    }

    /** @test */
    public function it_requires_submitted_attendance_status()
    {
        // Marquer la réunion comme non soumise
        $this->meeting->update(['attendance_status' => 'draft']);

        $response = $this->actingAs($this->user)
            ->postJson("/meetings/{$this->meeting->id}/validate-attendance");

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Les présences doivent être soumises avant d\'être validées.'
        ]);
    }

    /** @test */
    public function it_requires_authorized_user()
    {
        // Créer un utilisateur sans les droits
        $unauthorizedUser = User::factory()->create();
        $unauthorizedUser->assignRole('secretaire');

        $response = $this->actingAs($unauthorizedUser)
            ->postJson("/meetings/{$this->meeting->id}/validate-attendance");

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Vous n\'avez pas les droits pour valider les présences de cette réunion.'
        ]);
    }
} 
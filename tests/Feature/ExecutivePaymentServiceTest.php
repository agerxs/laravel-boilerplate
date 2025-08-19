<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\LocalCommittee;
use App\Models\Meeting;
use App\Models\PaymentRate;
use App\Models\MeetingPayment;
use App\Services\ExecutivePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutivePaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_secretaire_gets_paid_for_validated_meetings()
    {
        // Création du rôle secretaire
        $role = Role::firstOrCreate(['name' => 'secretaire']);
        // Création du comité local
        $committee = LocalCommittee::factory()->create();
        // Création du secrétaire
        $secretaire = User::factory()->create(['locality_id' => $committee->locality_id]);
        $secretaire->assignRole($role);
        // Taux de paiement pour le rôle
        PaymentRate::create(['role' => 'secretaire', 'meeting_rate' => 10000, 'is_active' => true]);
        // Création de 4 réunions validées pour ce comité
        $meetings = Meeting::factory()->count(4)->create([
            'local_committee_id' => $committee->id,
            'status' => 'validated',
        ]);
        // On prend la dernière réunion pour déclencher la génération
        $lastMeeting = $meetings->last();
        $service = new ExecutivePaymentService();
        $nbPaiements = $service->generatePaymentsForMeeting($lastMeeting);
        // 4 réunions => 2 paiements (1 paiement pour chaque groupe de 2)
        $this->assertEquals(2, $nbPaiements);
        $this->assertEquals(2, MeetingPayment::where('user_id', $secretaire->id)->count());
        $this->assertDatabaseHas('meeting_payments', [
            'user_id' => $secretaire->id,
            'amount' => 10000,
        ]);
    }

    public function test_sous_prefet_gets_paid_for_validated_meetings()
    {
        // Test simplifié pour identifier le problème
        $role = Role::firstOrCreate(['name' => 'president']);
        $committee = LocalCommittee::factory()->create();
        $sousPrefet = User::factory()->create(['locality_id' => $committee->locality_id]);
        $sousPrefet->assignRole($role);
        PaymentRate::create(['role' => 'president', 'meeting_rate' => 15000, 'is_active' => true]);
        
        // Créer seulement 2 réunions pour simplifier
        $meetings = Meeting::factory()->count(2)->create([
            'local_committee_id' => $committee->id,
            'status' => 'validated',
        ]);
        
        $lastMeeting = $meetings->last();
        $service = new ExecutivePaymentService();
        $nbPaiements = $service->generatePaymentsForMeeting($lastMeeting);
        
        // 2 réunions => 1 paiement
        $this->assertEquals(1, $nbPaiements);
        $this->assertEquals(1, MeetingPayment::where('user_id', $sousPrefet->id)->count());
    }

    public function test_no_payment_if_less_than_two_validated_meetings()
    {
        $role = Role::firstOrCreate(['name' => 'secretaire']);
        $committee = LocalCommittee::factory()->create();
        $secretaire = User::factory()->create(['locality_id' => $committee->locality_id]);
        $secretaire->assignRole($role);
        PaymentRate::create(['role' => 'secretaire', 'meeting_rate' => 10000, 'is_active' => true]);
        $meeting = Meeting::factory()->create([
            'local_committee_id' => $committee->id,
            'status' => 'validated',
        ]);
        $service = new ExecutivePaymentService();
        $nbPaiements = $service->generatePaymentsForMeeting($meeting);
        $this->assertEquals(0, $nbPaiements);
        $this->assertEquals(0, MeetingPayment::where('user_id', $secretaire->id)->count());
    }

    public function test_no_duplicate_payment_if_already_paid()
    {
        $role = Role::firstOrCreate(['name' => 'secretaire']);
        $committee = LocalCommittee::factory()->create();
        $secretaire = User::factory()->create(['locality_id' => $committee->locality_id]);
        $secretaire->assignRole($role);
        PaymentRate::create(['role' => 'secretaire', 'meeting_rate' => 10000, 'is_active' => true]);
        // 4 réunions validées
        $meetings = Meeting::factory()->count(4)->create([
            'local_committee_id' => $committee->id,
            'status' => 'validated',
        ]);
        $service = new ExecutivePaymentService();
        // Premier appel : 2 paiements doivent être créés
        $nbPaiements1 = $service->generatePaymentsForMeeting($meetings->last());
        $this->assertEquals(2, $nbPaiements1);
        $this->assertEquals(2, MeetingPayment::where('user_id', $secretaire->id)->count());
        // Deuxième appel : aucun nouveau paiement ne doit être créé
        $nbPaiements2 = $service->generatePaymentsForMeeting($meetings->last());
        $this->assertEquals(0, $nbPaiements2);
        $this->assertEquals(2, MeetingPayment::where('user_id', $secretaire->id)->count());
    }

    public function test_multiple_executives_in_same_locality_get_paid()
    {
        $roleSecretaire = Role::firstOrCreate(['name' => 'secretaire']);
        $roleSousPrefet = Role::firstOrCreate(['name' => 'president']);
        $committee = LocalCommittee::factory()->create();
        $secretaire = User::factory()->create(['locality_id' => $committee->locality_id]);
        $secretaire->assignRole($roleSecretaire);
        $sousPrefet = User::factory()->create(['locality_id' => $committee->locality_id]);
        $sousPrefet->assignRole($roleSousPrefet);
        PaymentRate::create(['role' => 'secretaire', 'meeting_rate' => 10000, 'is_active' => true]);
        PaymentRate::create(['role' => 'president', 'meeting_rate' => 15000, 'is_active' => true]);
        $meetings = Meeting::factory()->count(4)->create([
            'local_committee_id' => $committee->id,
            'status' => 'validated',
        ]);
        $service = new ExecutivePaymentService();
        $nbPaiements = $service->generatePaymentsForMeeting($meetings->last());
        // 4 réunions => 2 paiements pour chaque cadre
        $this->assertEquals(4, $nbPaiements);
        $this->assertEquals(2, MeetingPayment::where('user_id', $secretaire->id)->count());
        $this->assertEquals(2, MeetingPayment::where('user_id', $sousPrefet->id)->count());
        $this->assertDatabaseHas('meeting_payments', [
            'user_id' => $secretaire->id,
            'amount' => 10000,
            'role' => 'secretaire',
        ]);
        $this->assertDatabaseHas('meeting_payments', [
            'user_id' => $sousPrefet->id,
            'amount' => 15000,
            'role' => 'president',
        ]);
    }
} 
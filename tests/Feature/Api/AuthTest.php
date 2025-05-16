<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\LocalCommittee;
use App\Models\LocalCommitteeMember;
use App\Models\Locality;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_secretary_account()
    {
        // Créer une localité
        $locality = Locality::factory()->create();

        // Créer un comité local
        $committee = LocalCommittee::factory()->create([
            'name' => 'Comité Local Test',
            'locality_id' => $locality->id,
            'status' => 'active'
        ]);

        // Créer un utilisateur secrétaire
        $user = User::factory()->create([
            'name' => 'Secretaire Test',
            'email' => 'secretaire@test.com',
            'password' => bcrypt('password')
        ]);

        // Assigner le rôle de secrétaire
        $user->assignRole('secretaire');

        // Créer un membre du comité local
        $member = LocalCommitteeMember::factory()->create([
            'user_id' => $user->id,
            'local_committee_id' => $committee->id,
            'role' => 'secretary',
            'status' => 'active'
        ]);

        // Tenter la connexion
        $response = $this->postJson('/api/login', [
            'email' => 'secretaire@test.com',
            'password' => 'password'
        ]);

        // Vérifier la réponse
        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'roles',
                    'local_committees' => [
                        '*' => [
                            'id',
                            'name',
                            'locality_id',
                            'president_id',
                            'installation_date',
                            'ano_validation_date',
                            'fund_transmission_date',
                            'villages_count',
                            'population_rgph',
                            'population_to_enroll',
                            'status',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]
            ])
            ->assertJson([
                'user' => [
                    'name' => 'Secretaire Test',
                    'email' => 'secretaire@test.com',
                    'roles' => ['secretaire'],
                    'local_committees' => [
                        [
                            'id' => $committee->id,
                            'name' => 'Comité Local Test',
                            'status' => 'active'
                        ]
                    ]
                ]
            ]);
    }
} 
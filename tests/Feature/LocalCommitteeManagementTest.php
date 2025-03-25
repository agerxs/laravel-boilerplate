<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LocalCommittee;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LocalCommitteeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function authorized_user_can_create_local_committee()
    {
        $user = User::factory()->create()->assignRole('admin');
        $this->actingAs($user);

        $response = $this->post(route('local-committees.store'), [
            'name' => 'Comité Local de Test',
            'locality_id' => 1,
            'members' => [
                [
                    'user_id' => User::factory()->create()->id,
                    'role' => 'president',
                    'status' => 'active'
                ],
                [
                    'user_id' => User::factory()->create()->id,
                    'role' => 'secretary',
                    'status' => 'active'
                ]
            ],
            'decree_file' => UploadedFile::fake()->create('decree.pdf', 100),
            'installation_date' => now()->format('Y-m-d'),
            'installation_location' => 'Salle de réunion',
            'installation_minutes_file' => UploadedFile::fake()->create('minutes.pdf', 100),
            'attendance_list_file' => UploadedFile::fake()->create('attendance.pdf', 100)
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('local_committees', ['name' => 'Comité Local de Test']);
    }

    /** @test */
    public function it_requires_permanent_members()
    {
        $user = User::factory()->create()->assignRole('admin');
        $this->actingAs($user);

        $response = $this->post(route('local-committees.store'), [
            'name' => 'Comité Local de Test',
            'locality_id' => 1,
            'members' => []
        ]);

        $response->assertSessionHasErrors(['members']);
    }

    /** @test */
    public function it_can_add_village_representatives()
    {
        $committee = LocalCommittee::factory()->create();
        $village = Village::factory()->create();

        $response = $this->post(route('local-committees.add-representatives', $committee), [
            'village_id' => $village->id,
            'representatives' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'role' => 'Chef du village',
                    'phone' => '1234567890'
                ]
            ]
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('village_representatives', [
            'village_id' => $village->id,
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);
    }
} 
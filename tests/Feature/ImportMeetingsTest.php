<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LocalCommittee;
use App\Models\Locality;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MeetingsImport;

class ImportMeetingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_import_functionality()
    {
        $user = User::factory()->create();
        $committee = LocalCommittee::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('meetings.create-multiple'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Meetings/CreateMultiple'));
    }

    public function test_can_import_valid_excel_file()
    {
        $user = User::factory()->create();
        $committee = LocalCommittee::factory()->create();

        // Créer un fichier Excel de test
        $file = UploadedFile::fake()->create('meetings.xlsx', 100);

        Excel::fake();
        Excel::shouldReceive('import')
            ->once()
            ->andReturnUsing(function ($import, $file) {
                $import->data = [
                    [
                        'title' => 'Réunion test 1',
                        'scheduled_date' => '2024-01-15',
                        'scheduled_time' => '14:00',
                        'location' => 'Salle A'
                    ],
                    [
                        'title' => 'Réunion test 2',
                        'scheduled_date' => '2024-02-15',
                        'scheduled_time' => '15:00',
                        'location' => 'Salle B'
                    ]
                ];
            });

        $response = $this->actingAs($user)
            ->post(route('meetings.import'), [
                'file' => $file,
                'local_committee_id' => $committee->id
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_validates_file_type()
    {
        $user = User::factory()->create();
        $committee = LocalCommittee::factory()->create();

        $file = UploadedFile::fake()->create('meetings.txt', 100);

        $response = $this->actingAs($user)
            ->post(route('meetings.import'), [
                'file' => $file,
                'local_committee_id' => $committee->id
            ]);

        $response->assertSessionHasErrors(['file']);
    }

    public function test_validates_committee_exists()
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('meetings.xlsx', 100);

        $response = $this->actingAs($user)
            ->post(route('meetings.import'), [
                'file' => $file,
                'local_committee_id' => 999
            ]);

        $response->assertSessionHasErrors(['local_committee_id']);
    }

    public function test_can_download_template()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('templates.meetings'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}

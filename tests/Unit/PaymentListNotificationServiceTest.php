<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PaymentListNotificationService;
use App\Models\User;
use App\Models\Meeting;
use App\Models\LocalCommittee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PaymentListGenerated;

class PaymentListNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_can_detect_treasurers()
    {
        // Créer des utilisateurs trésoriers
        $treasurer1 = User::create([
            'name' => 'Trésorier 1',
            'email' => 'tresorier1@test.com',
            'password' => bcrypt('password'),
        ]);
        $treasurer1->assignRole('tresorier');

        $treasurer2 = User::create([
            'name' => 'Trésorier 2',
            'email' => 'tresorier2@test.com',
            'password' => bcrypt('password'),
        ]);
        $treasurer2->assignRole('tresorier');

        $notificationService = new PaymentListNotificationService();

        $this->assertTrue($notificationService->hasTreasurers());
        $this->assertEquals(2, $notificationService->getTreasurersCount());
    }

    public function test_service_returns_false_when_no_treasurers()
    {
        $notificationService = new PaymentListNotificationService();

        $this->assertFalse($notificationService->hasTreasurers());
        $this->assertEquals(0, $notificationService->getTreasurersCount());
    }

    public function test_notification_sent_to_treasurers()
    {
        Notification::fake();

        // Créer un trésorier
        $treasurer = User::create([
            'name' => 'Trésorier Test',
            'email' => 'tresorier@test.com',
            'password' => bcrypt('password'),
        ]);
        $treasurer->assignRole('tresorier');

        // Créer un secrétaire
        $secretary = User::create([
            'name' => 'Secrétaire Test',
            'email' => 'secretaire@test.com',
            'password' => bcrypt('password'),
        ]);
        $secretary->assignRole('secretaire');

        // Créer une réunion simple
        $meeting = Meeting::create([
            'title' => 'Réunion Test',
            'scheduled_date' => now(),
            'status' => 'completed'
        ]);

        $notificationService = new PaymentListNotificationService();
        $notificationService->notifyTreasurers(
            $meeting,
            $secretary,
            'test_file.csv',
            5
        );

        Notification::assertSentTo($treasurer, PaymentListGenerated::class);
    }

    public function test_no_notification_sent_when_no_treasurers()
    {
        Notification::fake();

        // Créer un secrétaire sans trésoriers
        $secretary = User::create([
            'name' => 'Secrétaire Test',
            'email' => 'secretaire@test.com',
            'password' => bcrypt('password'),
        ]);
        $secretary->assignRole('secretaire');

        $meeting = Meeting::create([
            'title' => 'Réunion Test',
            'scheduled_date' => now(),
            'status' => 'completed'
        ]);

        $notificationService = new PaymentListNotificationService();
        $notificationService->notifyTreasurers(
            $meeting,
            $secretary,
            'test_file.csv',
            3
        );

        // Vérifier qu'aucune notification n'a été envoyée
        Notification::assertNotSentTo($secretary, PaymentListGenerated::class);
    }
} 
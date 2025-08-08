<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Meeting;
use App\Models\LocalCommittee;
use App\Services\PaymentListNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PaymentListGenerated;

class PaymentListNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_treasurers_receive_notification_when_payment_list_is_submitted()
    {
        Notification::fake();

        // Créer un comité local
        $committee = LocalCommittee::create([
            'name' => 'Comité Test',
            'locality_id' => 1,
        ]);

        // Créer une réunion
        $meeting = Meeting::create([
            'title' => 'Réunion Test',
            'local_committee_id' => $committee->id,
            'scheduled_date' => now(),
            'status' => 'completed'
        ]);

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

        // Créer un utilisateur secrétaire
        $secretary = User::create([
            'name' => 'Secrétaire Test',
            'email' => 'secretaire@test.com',
            'password' => bcrypt('password'),
        ]);
        $secretary->assignRole('secretaire');

        // Tester le service de notification
        $notificationService = new PaymentListNotificationService();
        $notificationService->notifyTreasurers(
            $meeting,
            $secretary,
            'test_payment_list.csv',
            3
        );

        // Vérifier que les notifications ont été envoyées aux trésoriers
        Notification::assertSentTo($treasurer1, PaymentListGenerated::class);
        Notification::assertSentTo($treasurer2, PaymentListGenerated::class);
    }

    public function test_no_notification_sent_when_no_treasurers_exist()
    {
        Notification::fake();

        // Créer une réunion sans trésoriers
        $meeting = Meeting::create([
            'title' => 'Réunion Test',
            'scheduled_date' => now(),
            'status' => 'completed'
        ]);
        
        $secretary = User::create([
            'name' => 'Secrétaire Test',
            'email' => 'secretaire@test.com',
            'password' => bcrypt('password'),
        ]);
        $secretary->assignRole('secretaire');

        $notificationService = new PaymentListNotificationService();
        $notificationService->notifyTreasurers(
            $meeting,
            $secretary,
            'test_payment_list.csv',
            2
        );

        // Vérifier qu'aucune notification n'a été envoyée
        Notification::assertNotSentTo($secretary, PaymentListGenerated::class);
    }

    public function test_service_methods_work_correctly()
    {
        // Créer des trésoriers
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

        // Tester les méthodes du service
        $this->assertTrue($notificationService->hasTreasurers());
        $this->assertEquals(2, $notificationService->getTreasurersCount());

        // Supprimer les trésoriers
        $treasurer1->delete();
        $treasurer2->delete();

        $this->assertFalse($notificationService->hasTreasurers());
        $this->assertEquals(0, $notificationService->getTreasurersCount());
    }
} 
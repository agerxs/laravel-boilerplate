<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\MeetingMinutesSent;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TestEmailSending extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-sending 
                            {--meeting_id= : ID de la réunion à utiliser pour le test}
                            {--email= : Email de destination pour le test (défaut: test@example.com)}
                            {--type=minutes : Type de test (minutes, simple, notification)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test l\'envoi d\'emails avec Mailtrap pour vérifier la configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Test d\'envoi d\'emails avec Mailtrap');
        $this->newLine();

        // Récupérer les options
        $meetingId = $this->option('meeting_id');
        $testEmail = $this->option('email') ?: 'test@example.com';
        $testType = $this->option('type');

        // Afficher la configuration actuelle
        $this->info('📧 Configuration actuelle des emails :');
        $this->line('   Mailer: ' . config('mail.default'));
        $this->line('   Host: ' . config('mail.mailers.smtp.host'));
        $this->line('   Port: ' . config('mail.mailers.smtp.port'));
        $this->line('   Username: ' . config('mail.mailers.smtp.username'));
        $this->line('   Encryption: ' . config('mail.mailers.smtp.scheme'));
        $this->newLine();

        try {
            switch ($testType) {
                case 'minutes':
                    $this->testMeetingMinutesEmail($meetingId, $testEmail);
                    break;
                case 'simple':
                    $this->testSimpleEmail($testEmail);
                    break;
                case 'notification':
                    $this->testNotificationEmail($testEmail);
                    break;
                default:
                    $this->error('Type de test invalide. Utilisez: minutes, simple, ou notification');
                    return 1;
            }

            $this->newLine();
            $this->info('✅ Test terminé avec succès !');
            $this->line('Vérifiez votre boîte Mailtrap pour voir l\'email reçu.');
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors du test : ' . $e->getMessage());
            Log::error('Erreur test email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Test d'envoi d'un email de compte rendu de réunion
     */
    private function testMeetingMinutesEmail($meetingId, $testEmail)
    {
        $this->info('📋 Test d\'envoi d\'email de compte rendu de réunion...');

        // Récupérer ou créer une réunion de test
        if ($meetingId) {
            $meeting = Meeting::find($meetingId);
            if (!$meeting) {
                throw new \Exception("Réunion avec l'ID {$meetingId} non trouvée");
            }
        } else {
            // Créer une réunion de test
            $meeting = Meeting::first();
            if (!$meeting) {
                throw new \Exception("Aucune réunion trouvée dans la base de données");
            }
        }

        $this->line("   Utilisation de la réunion: {$meeting->title} (ID: {$meeting->id})");

        // Vérifier si la réunion a des minutes
        if (!$meeting->minutes) {
            $this->warn("   ⚠️  Cette réunion n'a pas de compte rendu, création d'un compte rendu de test...");
            
            // Créer des minutes de test
            $meeting->minutes()->create([
                'content' => 'Compte rendu de test pour vérification des emails',
                'status' => 'draft',
                'difficulties' => 'Difficultés de test pour validation des champs obligatoires',
                'recommendations' => 'Recommandations de test pour validation des champs obligatoires',
            ]);
            
            $this->line("   ✅ Compte rendu de test créé");
        }

        // Envoyer l'email
        $this->line("   📤 Envoi de l'email à {$testEmail}...");
        
        Mail::to($testEmail)->send(new MeetingMinutesSent($meeting));
        
        $this->info("   ✅ Email de compte rendu envoyé avec succès !");
    }

    /**
     * Test d'envoi d'un email simple
     */
    private function testSimpleEmail($testEmail)
    {
        $this->info('📧 Test d\'envoi d\'email simple...');

        $this->line("   📤 Envoi d'un email simple à {$testEmail}...");

        Mail::raw('Ceci est un email de test pour vérifier la configuration Mailtrap.

Ceci est un email de test simple pour vérifier que la configuration des emails fonctionne correctement.

Si vous recevez cet email, la configuration Mailtrap est opérationnelle !', function ($message) use ($testEmail) {
            $message->to($testEmail)
                    ->subject('Test Email - Configuration Mailtrap');
        });

        $this->info("   ✅ Email simple envoyé avec succès !");
    }

    /**
     * Test d'envoi d'une notification par email
     */
    private function testNotificationEmail($testEmail)
    {
        $this->info('🔔 Test d\'envoi de notification par email...');

        // Créer un utilisateur de test
        $testUser = User::firstOrCreate(
            ['email' => $testEmail],
            [
                'name' => 'Utilisateur Test',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->line("   👤 Utilisateur de test: {$testUser->name} ({$testUser->email})");

        // Envoyer une notification de test
        $this->line("   📤 Envoi de la notification de test...");

        // Envoyer un email simple de test
        Mail::raw('Ceci est un email de test pour vérifier la configuration Mailtrap.', function ($message) use ($testEmail) {
            $message->to($testEmail)
                    ->subject('Test de notification - Configuration Mailtrap');
        });

        $this->info("   ✅ Notification de test envoyée avec succès !");
    }
}

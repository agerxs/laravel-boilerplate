<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeviceTracking;
use App\Models\AppDownload;
use App\Models\AppVersion;
use App\Models\DeviceSession;
use App\Models\User;

class DeviceTrackingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des versions d'APK de test si elles n'existent pas
        $version1 = AppVersion::firstOrCreate(
            ['version_code' => 1],
            [
                'version_name' => '1.0.0',
                'apk_file' => 'apks/test_v1.apk',
                'release_notes' => 'Version initiale'
            ]
        );

        $version2 = AppVersion::firstOrCreate(
            ['version_code' => 2],
            [
                'version_name' => '2.0.0',
                'apk_file' => 'apks/test_v2.apk',
                'release_notes' => 'Version avec nouvelles fonctionnalités'
            ]
        );

        // Créer des tablettes de test (Android uniquement)
        $devices = [
            [
                'device_id' => 'dev_test_samsung_tab_s8',
                'device_name' => 'Samsung Galaxy Tab S8',
                'device_model' => 'SM-X700',
                'platform' => 'android',
                'platform_version' => '13',
                'app_version' => '2.0.0',
                'app_build_number' => '2',
                'screen_resolution' => '1600x2560',
                'screen_density' => '2.0x',
                'locale' => 'fr_FR',
                'timezone' => 'Europe/Paris',
                'is_tablet' => true,
                'is_emulator' => false,
                'last_seen_at' => now(),
            ],
            [
                'device_id' => 'dev_test_samsung_tab_a8',
                'device_name' => 'Samsung Galaxy Tab A8',
                'device_model' => 'SM-X200',
                'platform' => 'android',
                'platform_version' => '12',
                'app_version' => '2.0.0',
                'app_build_number' => '2',
                'screen_resolution' => '1200x1920',
                'screen_density' => '1.5x',
                'locale' => 'fr_FR',
                'timezone' => 'Europe/Paris',
                'is_tablet' => true,
                'is_emulator' => false,
                'last_seen_at' => now()->subHours(2),
            ],
            [
                'device_id' => 'dev_test_lenovo_tab_p11',
                'device_name' => 'Lenovo Tab P11',
                'device_model' => 'TB-J606F',
                'platform' => 'android',
                'platform_version' => '11',
                'app_version' => '1.0.0',
                'app_build_number' => '1',
                'screen_resolution' => '1200x2000',
                'screen_density' => '1.8x',
                'locale' => 'fr_FR',
                'timezone' => 'Europe/Paris',
                'is_tablet' => true,
                'is_emulator' => false,
                'last_seen_at' => now()->subDays(1),
            ],
            [
                'device_id' => 'dev_test_emulator_tablet',
                'device_name' => 'Android Tablet Emulator',
                'device_model' => 'sdk_gphone64_x86_64',
                'platform' => 'android',
                'platform_version' => '13',
                'app_version' => '2.0.0',
                'app_build_number' => '2',
                'screen_resolution' => '1200x1920',
                'screen_density' => '2.0x',
                'locale' => 'en_US',
                'timezone' => 'UTC',
                'is_tablet' => true,
                'is_emulator' => true,
                'last_seen_at' => now()->subHours(6),
            ],
        ];

        foreach ($devices as $deviceData) {
            $device = DeviceTracking::firstOrCreate(
                ['device_id' => $deviceData['device_id']],
                $deviceData
            );

            // Créer des sessions pour chaque appareil
            $session = DeviceSession::create([
                'device_id' => $device->id,
                'user_id' => null, // Pas d'utilisateur connecté pour les tests
                'session_type' => $device->is_tablet ? 'tablet' : 'mobile',
                'session_started_at' => now()->subHours(rand(1, 24)),
                'ip_address' => '192.168.1.' . rand(100, 200),
                'user_agent' => 'Mozilla/5.0 (Test Device)',
            ]);

            // Créer des téléchargements pour chaque appareil
            $downloadStatuses = ['completed', 'completed', 'failed', 'started'];
            $methods = ['app', 'web'];
            
            foreach ([$version1, $version2] as $version) {
                $status = $downloadStatuses[array_rand($downloadStatuses)];
                $method = $methods[array_rand($methods)];
                
                $download = AppDownload::create([
                    'device_id' => $device->id,
                    'app_version_id' => $version->id,
                    'user_id' => null,
                    'download_status' => $status,
                    'download_method' => $method,
                    'ip_address' => '192.168.1.' . rand(100, 200),
                    'user_agent' => 'Mozilla/5.0 (Test Device)',
                    'download_started_at' => now()->subDays(rand(1, 30)),
                    'download_completed_at' => $status === 'completed' ? now()->subDays(rand(1, 30)) : null,
                    'file_size' => $status === 'completed' ? rand(50000000, 150000000) : null, // 50-150 MB
                    'error_message' => $status === 'failed' ? 'Erreur de connexion' : null,
                ]);
            }
        }

        $this->command->info('✅ Données de test pour le suivi des tablettes créées avec succès !');
        $this->command->info('📱 Tablettes Android créées : ' . DeviceTracking::count());
        $this->command->info('📱 Tablettes Samsung : ' . DeviceTracking::where('device_name', 'like', '%Samsung%')->count());
        $this->command->info('📱 Tablettes Lenovo : ' . DeviceTracking::where('device_name', 'like', '%Lenovo%')->count());
        $this->command->info('📥 Téléchargements créés : ' . AppDownload::count());
        $this->command->info('🔐 Sessions créées : ' . DeviceSession::count());
    }
}

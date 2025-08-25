<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('device_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique(); // Identifiant unique de l'appareil
            $table->string('device_name')->nullable(); // Nom de l'appareil
            $table->string('device_model')->nullable(); // Modèle de l'appareil
            $table->string('platform'); // android, ios, web
            $table->string('platform_version')->nullable(); // Version du système d'exploitation
            $table->string('app_version')->nullable(); // Version actuelle de l'app
            $table->string('app_build_number')->nullable(); // Numéro de build
            $table->string('device_fingerprint')->nullable(); // Empreinte unique de l'appareil
            $table->string('screen_resolution')->nullable(); // Résolution d'écran
            $table->string('screen_density')->nullable(); // Densité d'écran
            $table->string('locale')->nullable(); // Langue de l'appareil
            $table->string('timezone')->nullable(); // Fuseau horaire
            $table->boolean('is_tablet')->default(false); // Est-ce une tablette ?
            $table->boolean('is_emulator')->default(false); // Est-ce un émulateur ?
            $table->json('additional_info')->nullable(); // Informations supplémentaires
            $table->timestamp('last_seen_at')->nullable(); // Dernière activité
            $table->timestamps();
        });

        Schema::create('app_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('device_tracking')->onDelete('cascade');
            $table->foreignId('app_version_id')->constrained('app_versions')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('download_status'); // started, completed, failed, cancelled
            $table->string('download_method')->default('app'); // app, web, direct
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('download_started_at')->nullable();
            $table->timestamp('download_completed_at')->nullable();
            $table->bigInteger('file_size')->nullable(); // Taille du fichier en bytes
            $table->string('error_message')->nullable(); // Message d'erreur si échec
            $table->json('download_metadata')->nullable(); // Métadonnées supplémentaires
            $table->timestamps();

            // Index pour les requêtes fréquentes
            $table->index(['device_id', 'app_version_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['download_status', 'created_at']);
        });

        Schema::create('device_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('device_tracking')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('session_token')->unique(); // Token de session unique
            $table->string('session_type')->default('mobile'); // mobile, tablet, web
            $table->timestamp('session_started_at');
            $table->timestamp('session_ended_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('session_data')->nullable(); // Données de session
            $table->timestamps();

            $table->index(['device_id', 'user_id']);
            $table->index(['session_token']);
            $table->index(['session_started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_sessions');
        Schema::dropIfExists('app_downloads');
        Schema::dropIfExists('device_tracking');
    }
};

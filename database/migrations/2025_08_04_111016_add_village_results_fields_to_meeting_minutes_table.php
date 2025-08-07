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
        Schema::table('meeting_minutes', function (Blueprint $table) {
            // Champs pour les résultats des villages
            $table->integer('people_to_enroll_count')->nullable()->comment('Nombre de personnes à enrôler');
            $table->integer('people_enrolled_count')->nullable()->comment('Nombre de personnes enrôlées');
            $table->integer('cmu_cards_available_count')->nullable()->comment('Nombre de cartes CMU disponibles');
            $table->integer('cmu_cards_distributed_count')->nullable()->comment('Nombre de cartes distribuées');
            $table->integer('complaints_received_count')->nullable()->comment('Réclamations reçues');
            $table->integer('complaints_processed_count')->nullable()->comment('Réclamations traitées');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_minutes', function (Blueprint $table) {
            $table->dropColumn([
                'people_to_enroll_count',
                'people_enrolled_count',
                'cmu_cards_available_count',
                'cmu_cards_distributed_count',
                'complaints_received_count',
                'complaints_processed_count'
            ]);
        });
    }
};

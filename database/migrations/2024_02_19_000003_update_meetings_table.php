<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            // Supprimer les anciennes colonnes
            //$table->dropForeign(['organizer_id']);
            $table->dropColumn([
               // 'description',
             //   'start_datetime',
             //   'end_datetime',
             //   'location',
             //   'organizer_id'
            ]);

            // Ajouter les nouvelles colonnes
           // $table->foreignId('local_committee_id')->constrained();
            $table->date('scheduled_date')->nullable();

            // Renommer la colonne status si elle existe déjà
            if (Schema::hasColumn('meetings', 'status')) {
                $table->string('status')->default('scheduled')->change();
            } else {
                $table->string('status')->default('scheduled');
            }

            // Ajouter une colonne pour le nombre de Personnes non enrôlées
            $table->integer('people_to_enroll_count')->nullable();

            // Ajouter une colonne pour le nombre de personnes enrôlées
            $table->integer('people_enrolled_count')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            // Supprimer les nouvelles colonnes
            $table->dropForeign(['local_committee_id']);
            $table->dropColumn(['local_committee_id', 'scheduled_date']);

            // Restaurer les anciennes colonnes
            $table->text('description')->nullable();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('organizer_id')->constrained('users');

            // Restaurer l'ancien format de status
            $table->string('status')->default('planned')->change();

            // Supprimer la colonne pour le nombre de Personnes non enrôlées
            $table->dropColumn('people_to_enroll_count');

            // Supprimer la colonne pour le nombre de personnes enrôlées
            $table->dropColumn('people_enrolled_count');
        });
    }
};

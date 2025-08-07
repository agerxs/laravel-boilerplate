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
        Schema::create('village_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('meeting_id')->index('village_results_meeting_id_foreign');
            $table->unsignedBigInteger('localite_id')->index('village_results_localite_id_foreign');
            $table->unsignedBigInteger('submitted_by')->nullable()->index('village_results_submitted_by_foreign');
            
            // Résultats du village
            $table->integer('people_to_enroll_count')->nullable()->comment('Nombre de personnes à enrôler');
            $table->integer('people_enrolled_count')->nullable()->comment('Nombre de personnes enrôlées');
            $table->integer('cmu_cards_available_count')->nullable()->comment('Nombre de cartes CMU disponibles');
            $table->integer('cmu_cards_distributed_count')->nullable()->comment('Nombre de cartes distribuées');
            $table->integer('complaints_received_count')->nullable()->comment('Réclamations reçues');
            $table->integer('complaints_processed_count')->nullable()->comment('Réclamations traitées');
            
            // Métadonnées
            $table->text('comments')->nullable()->comment('Commentaires du village');
            $table->enum('status', ['draft', 'submitted', 'validated'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable()->index('village_results_validated_by_foreign');
            $table->text('validation_comments')->nullable();
            
            $table->timestamps();
            
            // Contraintes
            $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade');
            $table->foreign('localite_id')->references('id')->on('localities')->onDelete('cascade');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
            
            // Index unique pour éviter les doublons
            $table->unique(['meeting_id', 'localite_id'], 'village_results_meeting_village_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('village_results');
    }
};

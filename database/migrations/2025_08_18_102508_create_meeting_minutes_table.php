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
        Schema::create('meeting_minutes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('meeting_id')->index('meeting_minutes_meeting_id_foreign');
            $table->text('content');
            $table->enum('status', ['draft', 'published', 'pending_validation', 'validated'])->nullable()->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('validation_requested_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable()->index('meeting_minutes_validated_by_foreign');
            $table->text('validation_comments')->nullable();
            $table->timestamps();
            $table->integer('people_to_enroll_count')->nullable()->comment('Nombre de personnes à enrôler');
            $table->integer('people_enrolled_count')->nullable()->comment('Nombre de personnes enrôlées');
            $table->integer('cmu_cards_available_count')->nullable()->comment('Nombre de cartes CMU disponibles');
            $table->integer('cmu_cards_distributed_count')->nullable()->comment('Nombre de cartes distribuées');
            $table->integer('complaints_received_count')->nullable()->comment('Réclamations reçues');
            $table->integer('complaints_processed_count')->nullable()->comment('Réclamations traitées');
            $table->text('difficulties')->nullable()->comment('Difficultés rencontrées pendant la réunion');
            $table->text('recommendations')->nullable()->comment('Recommandations et suggestions d\'amélioration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_minutes');
    }
};

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
        Schema::create('local_committee_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->unsignedBigInteger('locality_id')->nullable();
            $table->string('status')->nullable();
            $table->json('form_data')->nullable();
            $table->json('files')->nullable();
            $table->integer('last_active_step')->default(0);
            $table->timestamps();
            
            // Chaque utilisateur ne peut avoir qu'un seul brouillon en cours
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('local_committee_progress');
    }
}; 
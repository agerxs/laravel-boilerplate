<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('status')->default('pending'); // pending, accepted, declined
            $table->timestamps();
        });

        // Ajouter la contrainte check via une requête SQL brute
        DB::statement('ALTER TABLE meeting_participants ADD CONSTRAINT check_participant_type 
            CHECK ((user_id IS NOT NULL AND guest_email IS NULL) OR 
                  (user_id IS NULL AND guest_email IS NOT NULL))');
    }

    public function down()
    {
        // Supprimer la contrainte check avant de supprimer la table
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE meeting_participants DROP CONSTRAINT check_participant_type');
        }
        
        Schema::dropIfExists('meeting_participants');
    }
}; 
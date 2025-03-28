<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Table pour suivre l'état de synchronisation
        Schema::create('sync_status', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // meeting, attendee, etc.
            $table->unsignedBigInteger('entity_id');
            $table->string('status'); // pending, synced, failed
            $table->timestamp('last_sync_at')->nullable();
            $table->json('sync_data')->nullable();
            $table->timestamps();
        });

        // Table pour les opérations en attente
        Schema::create('sync_queue', function (Blueprint $table) {
            $table->id();
            $table->string('operation'); // create, update, delete
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->json('data');
            $table->timestamp('created_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
        });

        // Table pour les photos en attente de synchronisation
        Schema::create('offline_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendee_id');
            $table->string('local_path');
            $table->string('status')->default('pending');
            $table->timestamp('taken_at');
            $table->timestamps();
        });

        // Table pour les conflits
        Schema::create('sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->json('local_data');
            $table->json('server_data');
            $table->string('resolution')->nullable(); // local, server, merged
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sync_status');
        Schema::dropIfExists('sync_queue');
        Schema::dropIfExists('offline_photos');
        Schema::dropIfExists('sync_conflicts');
    }
}; 
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
        Schema::create('sync_status', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('status');
            $table->timestamp('last_sync_at')->nullable();
            $table->json('sync_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_status');
    }
};

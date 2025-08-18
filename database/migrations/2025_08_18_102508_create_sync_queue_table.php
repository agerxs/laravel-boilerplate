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
        Schema::create('sync_queue', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('operation');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->json('data');
            $table->timestamp('created_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_queue');
    }
};

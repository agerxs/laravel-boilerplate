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
        Schema::create('offline_photos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('attendee_id');
            $table->string('local_path');
            $table->string('status')->default('pending');
            $table->timestamp('taken_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_photos');
    }
};

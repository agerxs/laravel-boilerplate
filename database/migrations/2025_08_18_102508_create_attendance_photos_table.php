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
        Schema::create('attendance_photos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('attendee_id')->index('attendance_photos_attendee_id_foreign');
            $table->string('photo_url');
            $table->string('thumbnail_url');
            $table->integer('original_size');
            $table->integer('compressed_size');
            $table->timestamp('taken_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_photos');
    }
};

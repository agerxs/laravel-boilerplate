<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendee_id')->constrained('meeting_attendees')->onDelete('cascade');
            $table->string('photo_url');
            $table->string('thumbnail_url');
            $table->integer('original_size');
            $table->integer('compressed_size');
            $table->timestamp('taken_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_photos');
    }
}; 
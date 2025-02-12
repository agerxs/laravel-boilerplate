<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, accepted, declined
            $table->string('attendance_status')->nullable(); // present, absent, excused
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('meeting_participants');
    }
}; 
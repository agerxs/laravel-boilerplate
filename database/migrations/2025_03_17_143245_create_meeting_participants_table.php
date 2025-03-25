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
        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('meeting_id')->index('meeting_participants_meeting_id_foreign');
            $table->unsignedBigInteger('user_id')->nullable()->index('meeting_participants_user_id_foreign');
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_participants');
    }
};

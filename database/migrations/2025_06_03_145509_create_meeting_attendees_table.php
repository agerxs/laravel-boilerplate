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
        Schema::create('meeting_attendees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('meeting_id')->index('meeting_attendees_meeting_id_foreign');
            $table->unsignedBigInteger('localite_id')->index('meeting_attendees_localite_id_foreign');
            $table->unsignedBigInteger('representative_id')->nullable()->index('meeting_attendees_representative_id_foreign');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('role')->nullable();
            $table->enum('attendance_status', ['expected', 'present', 'absent', 'replaced'])->default('expected');
            $table->string('replacement_name')->nullable();
            $table->string('replacement_phone')->nullable();
            $table->string('replacement_role')->nullable();
            $table->timestamp('arrival_time')->nullable();
            $table->enum('payment_status', ['pending', 'processing', 'paid', 'cancelled'])->default('pending');
            $table->string('presence_photo')->nullable();
            $table->json('presence_location')->nullable();
            $table->timestamp('presence_timestamp')->nullable();
            $table->boolean('is_expected')->default(true);
            $table->boolean('is_present')->default(false);
            $table->text('comments')->nullable();
            $table->timestamps();
            $table->dateTime('last_modified')->nullable();
            $table->boolean('is_synced')->nullable()->default(false);
            $table->boolean('is_dirty')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_attendees');
    }
};

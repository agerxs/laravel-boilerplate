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
        Schema::create('meetings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('status')->default('scheduled');
            $table->timestamps();
            $table->unsignedBigInteger('local_committee_id');
            $table->date('scheduled_date')->nullable();
            $table->string('reschedule_reason')->nullable();
            $table->softDeletes();
            $table->integer('people_to_enroll_count')->nullable();
            $table->integer('people_enrolled_count')->nullable();
            $table->string('decree_file_path')->nullable();
            $table->string('installation_minutes_file_path')->nullable();
            $table->string('attendance_list_file_path')->nullable();
            $table->integer('target_enrollments')->default(0);
            $table->integer('actual_enrollments')->default(0);
            $table->time('scheduled_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};

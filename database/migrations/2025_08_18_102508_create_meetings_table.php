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
            $table->enum('status', ['planned', 'confirmed', 'completed', 'cancelled', 'prevalidated', 'validated', 'scheduled'])->nullable()->default('planned');
            $table->unsignedBigInteger('created_by')->nullable()->index('meetings_created_by_foreign');
            $table->unsignedBigInteger('bulk_import_id')->nullable()->index('meetings_bulk_import_id_foreign');
            $table->unsignedBigInteger('parent_meeting_id')->nullable()->index('meetings_parent_meeting_id_foreign');
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable()->index('meetings_confirmed_by_foreign');
            $table->timestamps();
            $table->unsignedBigInteger('local_committee_id');
            $table->dateTime('scheduled_date');
            $table->string('location')->nullable();
            $table->timestamp('prevalidated_at')->nullable();
            $table->unsignedBigInteger('prevalidated_by')->nullable()->index('meetings_prevalidated_by_foreign');
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable()->index('meetings_validated_by_foreign');
            $table->text('validation_comments')->nullable();
            $table->string('reschedule_reason')->nullable();
            $table->softDeletes();
            $table->integer('people_to_enroll_count')->nullable();
            $table->integer('people_enrolled_count')->nullable();
            $table->string('decree_file_path')->nullable();
            $table->string('installation_minutes_file_path')->nullable();
            $table->string('attendance_list_file_path')->nullable();
            $table->integer('target_enrollments')->default(0);
            $table->integer('actual_enrollments')->default(0);
            $table->dateTime('last_modified')->nullable();
            $table->boolean('is_synced')->nullable()->default(false);
            $table->boolean('is_dirty')->nullable()->default(false);
            $table->timestamp('attendance_validated_at')->nullable();
            $table->unsignedBigInteger('attendance_validated_by')->nullable()->index('meetings_attendance_validated_by_foreign');
            $table->enum('attendance_status', ['draft', 'submitted', 'validated'])->default('draft');
            $table->timestamp('attendance_submitted_at')->nullable();
            $table->unsignedBigInteger('attendance_submitted_by')->nullable()->index('meetings_attendance_submitted_by_foreign');
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

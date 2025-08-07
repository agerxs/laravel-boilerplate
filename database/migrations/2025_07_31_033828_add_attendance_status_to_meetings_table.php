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
        Schema::table('meetings', function (Blueprint $table) {
            $table->enum('attendance_status', ['draft', 'submitted', 'validated'])->default('draft');
            $table->timestamp('attendance_submitted_at')->nullable();
            $table->unsignedBigInteger('attendance_submitted_by')->nullable();
            $table->foreign('attendance_submitted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropForeign(['attendance_submitted_by']);
            $table->dropColumn(['attendance_status', 'attendance_submitted_at', 'attendance_submitted_by']);
        });
    }
};

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
            $table->timestamp('attendance_validated_at')->nullable();
            $table->unsignedBigInteger('attendance_validated_by')->nullable();
            $table->foreign('attendance_validated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropForeign(['attendance_validated_by']);
            $table->dropColumn(['attendance_validated_at', 'attendance_validated_by']);
        });
    }
};

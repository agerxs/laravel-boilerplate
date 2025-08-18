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
        Schema::table('attendance_photos', function (Blueprint $table) {
            $table->foreign(['attendee_id'])->references(['id'])->on('meeting_attendees')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_photos', function (Blueprint $table) {
            $table->dropForeign('attendance_photos_attendee_id_foreign');
        });
    }
};

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
        Schema::table('attendance_validation_tokens', function (Blueprint $table) {
            $table->foreign(['meeting_id'])->references(['id'])->on('meetings')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['used_by'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_validation_tokens', function (Blueprint $table) {
            $table->dropForeign('attendance_validation_tokens_meeting_id_foreign');
            $table->dropForeign('attendance_validation_tokens_used_by_foreign');
        });
    }
};

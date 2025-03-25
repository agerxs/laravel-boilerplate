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
        Schema::table('meeting_minutes_versions', function (Blueprint $table) {
            $table->foreign(['created_by'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['meeting_minutes_id'])->references(['id'])->on('meeting_minutes')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_minutes_versions', function (Blueprint $table) {
            $table->dropForeign('meeting_minutes_versions_created_by_foreign');
            $table->dropForeign('meeting_minutes_versions_meeting_minutes_id_foreign');
        });
    }
};

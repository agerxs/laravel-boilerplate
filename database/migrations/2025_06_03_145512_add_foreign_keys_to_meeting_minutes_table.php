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
        Schema::table('meeting_minutes', function (Blueprint $table) {
            $table->foreign(['meeting_id'])->references(['id'])->on('meetings')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['validated_by'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_minutes', function (Blueprint $table) {
            $table->dropForeign('meeting_minutes_meeting_id_foreign');
            $table->dropForeign('meeting_minutes_validated_by_foreign');
        });
    }
};

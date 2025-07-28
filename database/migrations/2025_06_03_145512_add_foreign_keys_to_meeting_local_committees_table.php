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
        Schema::table('meeting_local_committees', function (Blueprint $table) {
            $table->foreign(['local_committee_id'])->references(['id'])->on('local_committees')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['meeting_id'])->references(['id'])->on('meetings')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_local_committees', function (Blueprint $table) {
            $table->dropForeign('meeting_local_committees_local_committee_id_foreign');
            $table->dropForeign('meeting_local_committees_meeting_id_foreign');
        });
    }
};

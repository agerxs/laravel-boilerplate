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
        Schema::table('meeting_attendees', function (Blueprint $table) {
            $table->foreign(['localite_id'])->references(['id'])->on('localite')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['meeting_id'])->references(['id'])->on('meetings')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['representative_id'])->references(['id'])->on('representatives')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_attendees', function (Blueprint $table) {
            $table->dropForeign('meeting_attendees_localite_id_foreign');
            $table->dropForeign('meeting_attendees_meeting_id_foreign');
            $table->dropForeign('meeting_attendees_representative_id_foreign');
        });
    }
};

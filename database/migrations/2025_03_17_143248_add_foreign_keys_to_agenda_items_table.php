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
        Schema::table('agenda_items', function (Blueprint $table) {
            $table->foreign(['meeting_id'])->references(['id'])->on('meetings')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['presenter_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agenda_items', function (Blueprint $table) {
            $table->dropForeign('agenda_items_meeting_id_foreign');
            $table->dropForeign('agenda_items_presenter_id_foreign');
        });
    }
};

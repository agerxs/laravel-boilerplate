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
        Schema::table('meeting_payment_items', function (Blueprint $table) {
            $table->foreign(['attendee_id'])->references(['id'])->on('meeting_attendees')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['meeting_payment_list_id'])->references(['id'])->on('meeting_payment_lists')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_payment_items', function (Blueprint $table) {
            $table->dropForeign('meeting_payment_items_attendee_id_foreign');
            $table->dropForeign('meeting_payment_items_meeting_payment_list_id_foreign');
        });
    }
};

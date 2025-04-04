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
            $table->string('presence_photo')->nullable()->after('payment_status');
            $table->json('presence_location')->nullable()->after('presence_photo');
            $table->timestamp('presence_timestamp')->nullable()->after('presence_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_attendees', function (Blueprint $table) {
            $table->dropColumn(['presence_photo', 'presence_location', 'presence_timestamp']);
        });
    }
};

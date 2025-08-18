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
        Schema::table('enrollment_requests', function (Blueprint $table) {
            $table->foreign(['meeting_id'])->references(['id'])->on('meetings')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollment_requests', function (Blueprint $table) {
            $table->dropForeign('enrollment_requests_meeting_id_foreign');
        });
    }
};

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
        Schema::table('local_committee_members', function (Blueprint $table) {
            $table->foreign(['local_committee_id'])->references(['id'])->on('local_committees')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('local_committee_members', function (Blueprint $table) {
            $table->dropForeign('local_committee_members_local_committee_id_foreign');
            $table->dropForeign('local_committee_members_user_id_foreign');
        });
    }
};

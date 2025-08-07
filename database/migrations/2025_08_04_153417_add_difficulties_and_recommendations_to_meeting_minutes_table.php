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
            $table->text('difficulties')->nullable()->comment('Difficultés rencontrées pendant la réunion');
            $table->text('recommendations')->nullable()->comment('Recommandations et suggestions d\'amélioration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_minutes', function (Blueprint $table) {
            $table->dropColumn(['difficulties', 'recommendations']);
        });
    }
};

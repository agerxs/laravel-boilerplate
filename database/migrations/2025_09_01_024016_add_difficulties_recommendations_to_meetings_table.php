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
        Schema::table('meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('meetings', 'difficulties')) {
                $table->text('difficulties')->nullable()->comment('Difficultés rencontrées pendant la réunion');
            }
            if (!Schema::hasColumn('meetings', 'recommendations')) {
                $table->text('recommendations')->nullable()->comment('Recommandations et suggestions d\'amélioration');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            if (Schema::hasColumn('meetings', 'difficulties')) {
                $table->dropColumn('difficulties');
            }
            if (Schema::hasColumn('meetings', 'recommendations')) {
                $table->dropColumn('recommendations');
            }
        });
    }
};

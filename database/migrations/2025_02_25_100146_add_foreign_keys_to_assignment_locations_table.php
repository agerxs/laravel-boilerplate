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
        Schema::table('assignment_locations', function (Blueprint $table) {
            $table->foreign(['locality_id'])->references(['id'])->on('localities')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignment_locations', function (Blueprint $table) {
            $table->dropForeign('assignment_locations_locality_id_foreign');
        });
    }
};

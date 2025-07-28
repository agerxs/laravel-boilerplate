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
            $table->foreign(['locality_id'], 'assignment_locations_ibfk_1')->references(['id'])->on('localite')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignment_locations', function (Blueprint $table) {
            $table->dropForeign('assignment_locations_ibfk_1');
        });
    }
};

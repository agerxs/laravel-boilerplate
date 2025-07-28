<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mettre à jour tous les statuts 'scheduled' vers 'planned'
        DB::table('meetings')
            ->where('status', 'scheduled')
            ->update(['status' => 'planned']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remettre les statuts 'planned' vers 'scheduled' (si nécessaire)
        DB::table('meetings')
            ->where('status', 'planned')
            ->update(['status' => 'scheduled']);
    }
};

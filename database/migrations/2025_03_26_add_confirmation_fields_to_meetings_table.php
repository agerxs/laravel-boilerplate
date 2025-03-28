<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('status');
            $table->foreignId('confirmed_by')->nullable()->after('confirmed_at')
                  ->constrained('users')->nullOnDelete();
        });

        // Mettre à jour l'enum du statut pour inclure 'confirmed'
        DB::statement("ALTER TABLE meetings MODIFY COLUMN status ENUM('scheduled', 'confirmed', 'prevalidated', 'validated', 'cancelled', 'completed') NOT NULL DEFAULT 'scheduled'");
    }

    public function down()
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['confirmed_at', 'confirmed_by']);
        });

        // Restaurer l'enum du statut à sa version précédente
        DB::statement("ALTER TABLE meetings MODIFY COLUMN status ENUM('scheduled', 'prevalidated', 'validated', 'cancelled', 'completed') NOT NULL DEFAULT 'scheduled'");
    }
}; 
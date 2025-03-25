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
        // Modifier la colonne status pour accepter les nouveaux statuts
        DB::statement("ALTER TABLE meetings MODIFY COLUMN status ENUM('planned', 'completed', 'cancelled', 'prevalidated', 'validated') DEFAULT 'planned'");
        
        // Ajouter des colonnes pour le processus de prévalidation et validation
        Schema::table('meetings', function (Blueprint $table) {
            $table->timestamp('prevalidated_at')->nullable()->after('scheduled_date');
            $table->unsignedBigInteger('prevalidated_by')->nullable()->after('prevalidated_at');
            $table->timestamp('validated_at')->nullable()->after('prevalidated_by');
            $table->unsignedBigInteger('validated_by')->nullable()->after('validated_at');
            $table->text('validation_comments')->nullable()->after('validated_by');
            
            // Ajouter les clés étrangères
            $table->foreign('prevalidated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les nouvelles colonnes
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropForeign(['prevalidated_by']);
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['prevalidated_at', 'prevalidated_by', 'validated_at', 'validated_by', 'validation_comments']);
        });
        
        // Remettre le statut comme avant
        DB::statement("ALTER TABLE meetings MODIFY COLUMN status ENUM('planned', 'completed', 'cancelled') DEFAULT 'planned'");
    }
};

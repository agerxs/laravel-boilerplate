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
        DB::statement("ALTER TABLE meeting_minutes MODIFY COLUMN status ENUM('draft', 'published', 'pending_validation', 'validated') DEFAULT 'draft'");
        
        // Ajouter des colonnes pour le processus de validation
        Schema::table('meeting_minutes', function (Blueprint $table) {
            $table->timestamp('validation_requested_at')->nullable()->after('published_at');
            $table->timestamp('validated_at')->nullable()->after('validation_requested_at');
            $table->unsignedBigInteger('validated_by')->nullable()->after('validated_at');
            $table->text('validation_comments')->nullable()->after('validated_by');
            
            // Ajouter la clé étrangère pour validated_by
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les nouvelles colonnes
        Schema::table('meeting_minutes', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['validation_requested_at', 'validated_at', 'validated_by', 'validation_comments']);
        });
        
        // Remettre le statut comme avant
        DB::statement("ALTER TABLE meeting_minutes MODIFY COLUMN status ENUM('draft', 'published') DEFAULT 'draft'");
    }
};

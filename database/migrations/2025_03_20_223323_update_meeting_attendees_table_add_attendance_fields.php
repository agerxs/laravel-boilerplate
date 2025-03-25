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
        Schema::table('meeting_attendees', function (Blueprint $table) {
            // Renommer is_attending en is_expected pour plus de clarté
            $table->renameColumn('is_attending', 'is_expected');
            
            // Renommer was_present en is_present pour plus de cohérence
            $table->renameColumn('was_present', 'is_present');
            
            // Ajouter un champ pour le statut de présence
            $table->enum('attendance_status', ['expected', 'present', 'absent', 'replaced'])
                ->default('expected')
                ->after('role');
                
            // Ajouter des champs pour les remplacements
            $table->string('replacement_name')->nullable()->after('attendance_status');
            $table->string('replacement_phone')->nullable()->after('replacement_name');
            $table->string('replacement_role')->nullable()->after('replacement_phone');
            
            // Ajouter des champs pour l'horodatage et paiement
            $table->timestamp('arrival_time')->nullable()->after('replacement_role');
            $table->enum('payment_status', ['pending', 'processing', 'paid', 'cancelled'])
                ->default('pending')
                ->after('arrival_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_attendees', function (Blueprint $table) {
            // Supprimer les nouveaux champs
            $table->dropColumn([
                'attendance_status',
                'replacement_name',
                'replacement_phone',
                'replacement_role',
                'arrival_time',
                'payment_status'
            ]);
            
            // Renommer les colonnes à leur état d'origine
            $table->renameColumn('is_expected', 'is_attending');
            $table->renameColumn('is_present', 'was_present');
        });
    }
};

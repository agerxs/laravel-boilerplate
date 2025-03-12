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
        Schema::table('representatives', function (Blueprint $table) {
            // Ajouter la colonne local_committee_id après locality_id
            $table->foreignId('local_committee_id')->nullable()->after('locality_id');
            
            // Ajouter la contrainte de clé étrangère
            $table->foreign('local_committee_id')->references('id')->on('local_committees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('representatives', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère
            $table->dropForeign(['local_committee_id']);
            
            // Supprimer la colonne
            $table->dropColumn('local_committee_id');
        });
    }
}; 
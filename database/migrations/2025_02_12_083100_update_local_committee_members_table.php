<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('local_committee_members', function (Blueprint $table) {
            // Supprimer les anciennes colonnes
           

            // Ajouter la nouvelle colonne user_id
            //$table->foreignId('user_id')->after('local_committee_id')->constrained()->onDelete('cascade');
            
            // Ajouter la contrainte d'unicité
            $table->unique(['local_committee_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::table('local_committee_members', function (Blueprint $table) {
            // Supprimer la contrainte d'unicité
            $table->dropUnique(['local_committee_id', 'user_id']);
            
            // Supprimer la colonne user_id
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            // Recréer les anciennes colonnes
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->string('address');
        });
    }
}; 
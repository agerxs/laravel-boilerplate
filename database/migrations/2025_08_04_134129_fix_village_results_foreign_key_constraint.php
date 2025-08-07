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
        // Modifier le type de la colonne localite_id pour qu'elle soit compatible
        Schema::table('village_results', function (Blueprint $table) {
            $table->bigInteger('localite_id')->unsigned(false)->change();
        });
        
        Schema::table('village_results', function (Blueprint $table) {
            // Ajouter l'index s'il n'existe pas
            if (!Schema::hasIndex('village_results', 'village_results_localite_id_foreign')) {
                $table->index('localite_id', 'village_results_localite_id_foreign');
            }
            
            // Ajouter la contrainte de clé étrangère qui référence localite
            $table->foreign('localite_id', 'village_results_localite_id_foreign')
                  ->references('id')
                  ->on('localite')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('village_results', function (Blueprint $table) {
            // Supprimer la contrainte
            $table->dropForeign('village_results_localite_id_foreign');
            
            // Supprimer l'index
            $table->dropIndex('village_results_localite_id_foreign');
        });
        
        // Restaurer le type de colonne original
        Schema::table('village_results', function (Blueprint $table) {
            $table->bigInteger('localite_id')->unsigned()->change();
        });
    }
};

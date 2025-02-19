<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('local_committees', function (Blueprint $table) {
            // Ajouter d'abord la colonne locality_id
            $table->foreignId('locality_id')->nullable()->constrained();
            
            // Supprimer les anciennes colonnes si elles existent
            $table->dropColumn(['name', 'description', 'city', 'address']);

            // Ajouter les nouvelles colonnes
            $table->foreignId('president_id')->nullable()->constrained('users');
            $table->date('installation_date')->nullable();
            $table->date('ano_validation_date')->nullable();
            $table->date('fund_transmission_date')->nullable();
            $table->integer('villages_count')->nullable();
            $table->integer('population_rgph')->nullable();
            $table->integer('population_to_enroll')->nullable();
            $table->string('status')->default('pending');
        });
    }

    public function down(): void
    {
        Schema::table('local_committees', function (Blueprint $table) {
            // Supprimer les nouvelles colonnes
            $table->dropForeign(['locality_id']);
            $table->dropForeign(['president_id']);
            $table->dropColumn([
                'locality_id',
                'president_id',
                'installation_date',
                'ano_validation_date',
                'fund_transmission_date',
                'villages_count',
                'population_rgph',
                'population_to_enroll',
                'status'
            ]);

            // Restaurer les anciennes colonnes
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
        });
    }
}; 
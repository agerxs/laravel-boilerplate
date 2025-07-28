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
        Schema::create('bulk_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Utilisateur qui a fait l'import
            $table->unsignedBigInteger('local_committee_id'); // Comité local concerné
            $table->string('import_type')->default('meetings'); // Type d'import (meetings, etc.)
            $table->string('original_filename'); // Nom du fichier importé
            $table->string('file_path')->nullable(); // Chemin du fichier stocké
            $table->string('file_type')->nullable(); // Type MIME du fichier
            $table->integer('file_size')->nullable(); // Taille du fichier en bytes
            $table->json('import_data')->nullable(); // Données importées (pour référence)
            $table->integer('meetings_created')->default(0); // Nombre de réunions créées
            $table->integer('attachments_count')->default(0); // Nombre de pièces jointes
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable(); // Message d'erreur si échec
            $table->json('attachments_info')->nullable(); // Informations sur les pièces jointes
            $table->timestamps();
            $table->softDeletes();

            // Index pour les performances
            $table->index(['user_id', 'created_at']);
            $table->index(['local_committee_id', 'created_at']);
            $table->index(['status', 'created_at']);

            // Clés étrangères
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('local_committee_id')->references('id')->on('local_committees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_imports');
    }
};

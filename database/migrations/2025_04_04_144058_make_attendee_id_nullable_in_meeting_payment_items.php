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
        Schema::table('meeting_payment_items', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère
            $table->dropForeign(['attendee_id']);
            // Modifier la colonne pour la rendre nullable
            $table->unsignedBigInteger('attendee_id')->nullable()->change();
            // Recréer la contrainte de clé étrangère
            $table->foreign('attendee_id')->references('id')->on('meeting_attendees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_payment_items', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère
            $table->dropForeign(['attendee_id']);
            // Modifier la colonne pour la rendre non nullable
            $table->unsignedBigInteger('attendee_id')->nullable(false)->change();
            // Recréer la contrainte de clé étrangère
            $table->foreign('attendee_id')->references('id')->on('meeting_attendees');
        });
    }
};

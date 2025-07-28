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
        Schema::table('meeting_payments', function (Blueprint $table) {
            // Ajouter un statut de paiement
            $table->enum('payment_status', ['pending', 'validated', 'paid', 'cancelled'])->default('pending')->after('amount');
            
            // Ajouter les IDs des réunions qui ont généré ce paiement (JSON)
            $table->json('triggering_meetings')->nullable()->after('payment_status');
            
            // Ajouter des champs pour le suivi
            $table->timestamp('validated_at')->nullable()->after('payment_date');
            $table->unsignedBigInteger('validated_by')->nullable()->after('validated_at');
            $table->timestamp('paid_at')->nullable()->after('validated_by');
            $table->unsignedBigInteger('paid_by')->nullable()->after('paid_at');
            
            // Ajouter des clés étrangères
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('paid_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_payments', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropForeign(['paid_by']);
            $table->dropColumn([
                'payment_status',
                'triggering_meetings',
                'validated_at',
                'validated_by',
                'paid_at',
                'paid_by'
            ]);
        });
    }
};

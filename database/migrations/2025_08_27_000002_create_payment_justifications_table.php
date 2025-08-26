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
        Schema::create('payment_justifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('meeting_payment_list_id')->index();
            $table->unsignedBigInteger('uploaded_by')->index();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->bigInteger('file_size'); // en bytes
            $table->string('justification_type'); // reçu, quittance, preuve_virement, etc.
            $table->text('description')->nullable();
            $table->string('reference_number')->nullable(); // numéro de référence du paiement
            $table->decimal('amount', 10, 2)->nullable(); // montant justifié
            $table->date('payment_date')->nullable(); // date du paiement
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('meeting_payment_list_id')->references('id')->on('meeting_payment_lists')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_justifications');
    }
};

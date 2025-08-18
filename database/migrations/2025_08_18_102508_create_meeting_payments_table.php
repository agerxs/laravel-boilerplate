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
        Schema::create('meeting_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('meeting_id');
            $table->unsignedBigInteger('user_id')->index('meeting_payments_user_id_foreign');
            $table->string('role');
            $table->decimal('amount', 10);
            $table->enum('payment_status', ['pending', 'validated', 'paid', 'cancelled'])->default('pending');
            $table->json('triggering_meetings')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->date('payment_date')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable()->index('meeting_payments_validated_by_foreign');
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable()->index('meeting_payments_paid_by_foreign');
            $table->string('payment_method')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['meeting_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_payments');
    }
};

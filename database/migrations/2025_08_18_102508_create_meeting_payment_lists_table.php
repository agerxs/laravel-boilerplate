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
        Schema::create('meeting_payment_lists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('meeting_id')->index('meeting_payment_lists_meeting_id_foreign');
            $table->unsignedBigInteger('submitted_by')->nullable()->index('meeting_payment_lists_submitted_by_foreign');
            $table->unsignedBigInteger('validated_by')->nullable()->index('meeting_payment_lists_validated_by_foreign');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->string('status')->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->decimal('total_amount', 10)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_payment_lists');
    }
};

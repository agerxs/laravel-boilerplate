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
        Schema::create('meeting_payment_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('meeting_payment_list_id')->index('meeting_payment_items_meeting_payment_list_id_foreign');
            $table->unsignedBigInteger('attendee_id')->nullable()->index('meeting_payment_items_attendee_id_foreign');
            $table->decimal('amount', 10);
            $table->string('role');
            $table->string('payment_status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_payment_items');
    }
};

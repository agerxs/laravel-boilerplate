<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('meeting_payment_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->onDelete('cascade');
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->string('status')->default('draft'); // draft, submitted, validated, rejected
            $table->text('rejection_reason')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('meeting_payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_payment_list_id')->constrained()->onDelete('cascade');
            $table->foreignId('attendee_id')->nullable()->constrained('meeting_attendees');
            $table->decimal('amount', 10, 2);
            $table->string('role');
            $table->string('payment_status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('meeting_payment_items');
        Schema::dropIfExists('meeting_payment_lists');
    }
}; 
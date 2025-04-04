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
            $table->foreignId('submitted_by')->constrained('users');
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->enum('status', ['draft', 'submitted', 'validated', 'rejected'])->default('draft');
            $table->decimal('total_amount', 10, 2);
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('meeting_payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_payment_list_id')->constrained()->onDelete('cascade');
            $table->foreignId('attendee_id')->constrained('meeting_attendees');
            $table->decimal('amount', 10, 2);
            $table->string('role');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('meeting_payment_items');
        Schema::dropIfExists('meeting_payment_lists');
    }
}; 
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
        Schema::create('meeting_minutes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('meeting_id')->index('meeting_minutes_meeting_id_foreign');
            $table->text('content');
            $table->enum('status', ['draft', 'published', 'pending_validation', 'validated'])->nullable()->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('validation_requested_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable()->index('meeting_minutes_validated_by_foreign');
            $table->text('validation_comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_minutes');
    }
};

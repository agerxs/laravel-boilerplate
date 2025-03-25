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
        Schema::create('attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('meeting_id')->index('attachments_meeting_id_foreign');
            $table->string('title');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->string('nature');
            $table->unsignedBigInteger('size');
            $table->unsignedBigInteger('uploaded_by')->index('attachments_uploaded_by_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};

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
        Schema::create('meeting_minutes_versions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('meeting_minutes_id')->index('meeting_minutes_versions_meeting_minutes_id_foreign');
            $table->text('content');
            $table->string('version_number');
            $table->unsignedBigInteger('created_by')->nullable()->index('meeting_minutes_versions_created_by_foreign');
            $table->text('change_summary')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_minutes_versions');
    }
};

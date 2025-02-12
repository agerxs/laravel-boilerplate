<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_minutes_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_minutes_id')
                ->constrained()
                ->onDelete('cascade');
            $table->text('content');
            $table->string('version_number');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->text('change_summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_minutes_versions');
    }
}; 
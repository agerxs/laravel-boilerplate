<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_committees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('city');
            $table->string('address');
            $table->timestamps();
        });

        // Table pivot pour les membres du comité
        Schema::create('local_committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_committee_id')
                ->constrained()
                ->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->string('address');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');
            $table->string('role')->default('member'); // president, secretary, member, etc.
            $table->timestamps();
        });

        // Table pivot pour lier les comités aux réunions
        Schema::create('local_committee_meeting', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_committee_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('meeting_id')
                ->constrained()
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('local_committee_meeting');
        Schema::dropIfExists('local_committee_members');
        Schema::dropIfExists('local_committees');
    }
}; 
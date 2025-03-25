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
        Schema::create('meeting_attendees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('meeting_id')->index('meeting_attendees_meeting_id_foreign');
            $table->bigInteger('localite_id')->index('meeting_attendees_localite_id_foreign');
            $table->unsignedBigInteger('representative_id')->nullable()->index('meeting_attendees_representative_id_foreign');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_attending')->default(true);
            $table->boolean('was_present')->default(false);
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_attendees');
    }
};

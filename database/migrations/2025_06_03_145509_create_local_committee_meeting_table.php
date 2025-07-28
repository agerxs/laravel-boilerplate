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
        Schema::create('local_committee_meeting', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('local_committee_id')->index('local_committee_meeting_local_committee_id_foreign');
            $table->unsignedBigInteger('meeting_id')->index('local_committee_meeting_meeting_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('local_committee_meeting');
    }
};

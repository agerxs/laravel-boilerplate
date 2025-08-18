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
        Schema::create('sub_meeting_villages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sub_meeting_id')->index('sub_meeting_villages_sub_meeting_id_foreign');
            $table->unsignedBigInteger('village_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_meeting_villages');
    }
};

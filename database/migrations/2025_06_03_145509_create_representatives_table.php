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
        Schema::create('representatives', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('role');
            $table->unsignedBigInteger('locality_id');
            $table->unsignedBigInteger('local_committee_id')->nullable()->index('representatives_local_committee_id_foreign');
            $table->timestamps();
            $table->dateTime('last_modified')->nullable();
            $table->boolean('isSynced')->nullable()->default(false);
            $table->boolean('isDirty')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('representatives');
    }
};

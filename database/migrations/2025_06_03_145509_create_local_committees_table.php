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
        Schema::create('local_committees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
            $table->unsignedBigInteger('president_id')->nullable()->index('local_committees_president_id_foreign');
            $table->string('decree_file', 100)->nullable();
            $table->string('installation_minutes_file', 100)->nullable();
            $table->string('attendance_list_file', 100)->nullable();
            $table->string('installation_location', 100)->nullable();
            $table->date('installation_date')->nullable();
            $table->date('ano_validation_date')->nullable();
            $table->date('fund_transmission_date')->nullable();
            $table->integer('villages_count')->nullable();
            $table->integer('population_rgph')->nullable();
            $table->integer('population_to_enroll')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('locality_id')->nullable();
            $table->softDeletes();
            $table->dateTime('last_modified')->nullable();
            $table->boolean('is_synced')->nullable()->default(false);
            $table->boolean('is_dirty')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('local_committees');
    }
};

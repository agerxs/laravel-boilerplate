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
        Schema::create('local_committee_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('local_committee_id');
            $table->unsignedBigInteger('user_id')->nullable()->index('local_committee_members_user_id_foreign');
            $table->string('role')->default('member');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();

            $table->unique(['local_committee_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('local_committee_members');
    }
};

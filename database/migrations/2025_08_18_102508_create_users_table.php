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
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('must_change_password')->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->softDeletes();
            $table->bigInteger('locality_id')->nullable()->index('users_locality_id_foreign');
            $table->unsignedBigInteger('organization_id')->nullable()->index('users_organization_id_foreign');
            $table->unsignedBigInteger('job_function_id')->nullable()->index('users_job_function_id_foreign');
            $table->unsignedBigInteger('contract_type_id')->nullable()->index('users_contract_type_id_foreign');
            $table->unsignedBigInteger('assignment_location_id')->nullable()->index('users_assignment_location_id_foreign');
            $table->string('cni_number')->nullable();
            $table->string('cnam_number')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

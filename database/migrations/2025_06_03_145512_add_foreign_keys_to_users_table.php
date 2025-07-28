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
        Schema::table('users', function (Blueprint $table) {
            $table->foreign(['contract_type_id'])->references(['id'])->on('contract_types')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['locality_id'], 'users_ibfk_1')->references(['id'])->on('localite')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['assignment_location_id'], 'users_ibfk_2')->references(['id'])->on('assignment_locations')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['job_function_id'])->references(['id'])->on('job_functions')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['organization_id'])->references(['id'])->on('organizations')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_contract_type_id_foreign');
            $table->dropForeign('users_ibfk_1');
            $table->dropForeign('users_ibfk_2');
            $table->dropForeign('users_job_function_id_foreign');
            $table->dropForeign('users_organization_id_foreign');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('locality_id')->nullable()->constrained();
            $table->foreignId('organization_id')->nullable()->constrained();
            $table->foreignId('job_function_id')->nullable()->constrained();
            $table->foreignId('contract_type_id')->nullable()->constrained();
            $table->foreignId('assignment_location_id')->nullable()->constrained();
            $table->string('cni_number')->nullable()->unique();
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['locality_id']);
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['job_function_id']);
            $table->dropForeign(['contract_type_id']);
            $table->dropForeign(['assignment_location_id']);
            $table->dropColumn([
                'locality_id',
                'organization_id',
                'job_function_id',
                'contract_type_id',
                'assignment_location_id',
                'cni_number',
                'phone',
                'whatsapp'
            ]);
        });
    }
}; 
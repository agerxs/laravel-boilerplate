<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('structure_id')->nullable()->constrained();
            $table->foreignId('job_title_id')->nullable()->constrained();
            $table->foreignId('contract_type_id')->nullable()->constrained();
            $table->foreignId('assignment_post_id')->nullable()->constrained();
            $table->string('phone', 10)->nullable();
            $table->string('whatsapp', 10)->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['structure_id']);
            $table->dropForeign(['job_title_id']);
            $table->dropForeign(['contract_type_id']);
            $table->dropForeign(['assignment_post_id']);
            $table->dropColumn([
                'cni_number',
                'structure_id',
                'job_title_id',
                'contract_type_id',
                'assignment_post_id',
                'phone',
                'whatsapp',
                'deleted_at'
            ]);
        });
    }
};

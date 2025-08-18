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
        Schema::table('bulk_imports', function (Blueprint $table) {
            $table->foreign(['local_committee_id'])->references(['id'])->on('local_committees')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bulk_imports', function (Blueprint $table) {
            $table->dropForeign('bulk_imports_local_committee_id_foreign');
            $table->dropForeign('bulk_imports_user_id_foreign');
        });
    }
};

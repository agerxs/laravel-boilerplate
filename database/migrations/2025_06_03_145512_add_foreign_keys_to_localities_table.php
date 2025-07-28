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
        Schema::table('localities', function (Blueprint $table) {
            $table->foreign(['locality_type_id'])->references(['id'])->on('locality_types')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['parent_id'])->references(['id'])->on('localities')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('localities', function (Blueprint $table) {
            $table->dropForeign('localities_locality_type_id_foreign');
            $table->dropForeign('localities_parent_id_foreign');
        });
    }
};

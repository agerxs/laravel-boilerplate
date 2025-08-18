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
        Schema::create('locality_type', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('name');
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
            $table->bigInteger('type_localite_id')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locality_type');
    }
};

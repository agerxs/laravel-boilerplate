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
        Schema::create('localite', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('name');
            $table->bigInteger('parent_id')->nullable();
            $table->bigInteger('menage')->nullable();
            $table->bigInteger('quota_menage')->nullable();
            $table->bigInteger('locality_type_id');
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
            $table->bigInteger('quota_femme')->nullable();
            $table->bigInteger('quota_homme')->nullable();
            $table->string('code_officiel', 50)->nullable();
            $table->bigInteger('nombre_menage')->nullable();
            $table->double('taux_pauvrete')->nullable();
            $table->double('longitude')->nullable();
            $table->double('latitude')->nullable();
            $table->boolean('active')->nullable()->default(true);
            $table->bigInteger('structure_id')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('localite');
    }
};

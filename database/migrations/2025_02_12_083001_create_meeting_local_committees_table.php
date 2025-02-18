<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('meeting_local_committees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->onDelete('cascade');
            $table->foreignId('local_committee_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['meeting_id', 'local_committee_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('meeting_local_committees');
    }
}; 
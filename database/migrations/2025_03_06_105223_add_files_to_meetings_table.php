<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('decree_file_path')->nullable();
            $table->string('installation_minutes_file_path')->nullable();
            $table->string('attendance_list_file_path')->nullable();
        });
    }

    public function down()
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('decree_file_path');
            $table->dropColumn('installation_minutes_file_path');
            $table->dropColumn('attendance_list_file_path');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScheduledTimeToMeetingsTable extends Migration
{
    public function up()
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->time('scheduled_time')->nullable();
        });
    }

    public function down()
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('scheduled_time');
        });
    }
} 
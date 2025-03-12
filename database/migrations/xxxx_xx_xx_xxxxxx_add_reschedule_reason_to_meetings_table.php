<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRescheduleReasonToMeetingsTable extends Migration
{
    public function up()
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('reschedule_reason')->nullable()->after('scheduled_date');
        });
    }

    public function down()
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('reschedule_reason');
        });
    }
} 
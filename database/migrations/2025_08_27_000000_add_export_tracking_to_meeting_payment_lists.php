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
        Schema::table('meeting_payment_lists', function (Blueprint $table) {
            $table->timestamp('exported_at')->nullable()->after('validated_at');
            $table->unsignedBigInteger('exported_by')->nullable()->after('exported_at');
            $table->string('export_status')->default('not_exported')->after('exported_by');
            $table->string('export_reference')->nullable()->after('export_status');
            $table->timestamp('paid_at')->nullable()->after('export_reference');
            $table->unsignedBigInteger('paid_by')->nullable()->after('paid_at');
            
            $table->foreign('exported_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('paid_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_payment_lists', function (Blueprint $table) {
            $table->dropForeign(['exported_by', 'paid_by']);
            $table->dropColumn(['exported_at', 'exported_by', 'export_status', 'export_reference', 'paid_at', 'paid_by']);
        });
    }
};

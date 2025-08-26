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
        Schema::table('meeting_payment_items', function (Blueprint $table) {
            $table->timestamp('exported_at')->nullable()->after('comments');
            $table->string('export_reference')->nullable()->after('exported_at');
            $table->timestamp('paid_at')->nullable()->after('export_reference');
            $table->string('payment_reference')->nullable()->after('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_payment_items', function (Blueprint $table) {
            $table->dropColumn(['exported_at', 'export_reference', 'paid_at', 'payment_reference']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\LocalCommittee;
use App\Models\Locality;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('local_committees', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });

        // Mettre à jour les noms existants
        LocalCommittee::with('locality')->each(function ($committee) {
            $committee->update([
                'name' => "Comité Local de {$committee->locality->name}"
            ]);
        });

        // Rendre le champ obligatoire après la mise à jour
        Schema::table('local_committees', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('local_committees', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
}; 
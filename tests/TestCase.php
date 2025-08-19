<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\BeforeClass;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Vider le cache des permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les rôles s'ils n'existent pas
        $roles = ['admin', 'president', 'secretaire', 'prefet', 'tresorier'];
        foreach ($roles as $role) {
            if (!\Spatie\Permission\Models\Role::where('name', $role)->exists()) {
                \Spatie\Permission\Models\Role::create(['name' => $role]);
            }
        }

        $this->handleSQLiteSpecialCases();
    }

    /**
     * Effectuer le seed une seule fois avant tous les tests.
     */
    #[BeforeClass]
    public function seedDatabaseOnce()
    {
        $this->seed();
    }

    /**
     * Gérer les cas spéciaux pour SQLite
     */
    protected function handleSQLiteSpecialCases(): void
    {
        if (Config::get('database.default') !== 'sqlite') {
            return;
        }

        // Gérer les colonnes ENUM pour SQLite
        Schema::table('meeting_minutes', function ($table) {
            if (Schema::hasColumn('meeting_minutes', 'status')) {
                $table->string('status')->default('draft')->change();
            }
        });

        Schema::table('meetings', function ($table) {
            if (Schema::hasColumn('meetings', 'status')) {
                $table->string('status')->default('planned')->change();
            }
        });
    }
}

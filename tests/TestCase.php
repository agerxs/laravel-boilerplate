<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Effectuer le seed une seule fois avant tous les tests.
     *
     * @beforeClass
     */
    public function seedDatabaseOnce()
    {
        // Effectuer le seed avant tous les tests
        // Tu peux aussi vérifier si la base de données est déjà peuplée si nécessaire
        $this->seed();
    }
}

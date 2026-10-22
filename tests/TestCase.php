<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Las pruebas no necesitan compilar CSS/JS con Vite.
        $this->withoutVite();
    }
}

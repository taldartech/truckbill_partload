<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests render Inertia via app.blade.php (@vite). CI / PHPUnit
        // must not require a real Vite manifest for HTTP assertions to pass.
        $this->withoutVite();
    }
}

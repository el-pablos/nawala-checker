<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Vite;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable Vite for testing - use hot file to prevent manifest lookups
        Vite::useHotFile(storage_path('vite.hot'));

        // Set fake Telegram bot token for testing
        config(['services.telegram.bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11']);
    }
}

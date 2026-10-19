<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * PHPUnit's <env> entries (even with force="true") cannot override
     * variables inherited from the process environment in $_SERVER, and
     * Laravel's env() reads $_SERVER first. Normalize the leak-prone
     * config vars so tests are hermetic regardless of the shell.
     *
     * @var array<string, string>
     */
    private const FORCED_TEST_ENV = [
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => ':memory:',
        'SESSION_DRIVER' => 'array',
        'CACHE_STORE' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'MAIL_MAILER' => 'array',
    ];

    protected function setUp(): void
    {
        foreach (self::FORCED_TEST_ENV as $key => $value) {
            $_SERVER[$key] = $value;
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }

        parent::setUp();
    }
}

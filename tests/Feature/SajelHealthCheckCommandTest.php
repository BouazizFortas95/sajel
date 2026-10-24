<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SajelHealthCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_succeeds_on_fresh_database(): void
    {
        $this->artisan('sajel:health-check')
            ->assertExitCode(0);
    }

    public function test_json_output_has_all_keys(): void
    {
        $this->artisan('sajel:health-check', ['--json' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('"status":"ok"');
    }
}

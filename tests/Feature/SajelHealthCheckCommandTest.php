<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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
        $exitCode = Artisan::call('sajel:health-check', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($payload);
        $this->assertSame('ok', $payload['status']);

        foreach (['database', 'schema', 'timezone', 'localization', 'pwa', 'ai'] as $check) {
            $this->assertArrayHasKey($check, $payload['checks'], "Missing health check: {$check}");
            $this->assertArrayHasKey('status', $payload['checks'][$check]);
            $this->assertArrayHasKey('message', $payload['checks'][$check]);
        }
    }

    public function test_ai_check_warns_when_api_key_is_missing(): void
    {
        config([
            'ai.provider' => 'gemini',
            'ai.gemini.key' => null,
        ]);

        $exitCode = Artisan::call('sajel:health-check');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Provider: gemini', $output);
        $this->assertStringContainsString('Key: not configured', $output);
        $this->assertStringContainsString('[WARN] ai', $output);
    }

    public function test_ai_check_passes_when_api_key_is_configured(): void
    {
        config([
            'ai.provider' => 'gemini',
            'ai.gemini.key' => 'test-key',
            'ai.gemini.model' => 'gemini-test-model',
        ]);

        $exitCode = Artisan::call('sajel:health-check');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Provider: gemini (gemini-test-model)', $output);
        $this->assertStringContainsString('Key: configured', $output);
        $this->assertStringContainsString('[PASS] ai', $output);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SajelHealthCheckCommand extends Command
{
    protected $signature = 'sajel:health-check {--json : Output health report as JSON}';

    protected $description = 'Comprehensive Sajel health audit';

    protected $requiredTables = [
        'users', 'education_phases', 'levels', 'classrooms', 'groups', 'students', 'subjects',
        'schedules', 'daily_logs', 'attendances', 'assessments', 'grades', 'pedagogical_memos',
        'curriculum_domains', 'curriculum_sequences', 'curriculum_resources', 'labs',
        'lab_stations', 'lab_seating_plans', 'behavior_observations', 'active_learning_strategies',
        'pedagogical_remediations', 'pedagogical_meetings', 'school_holidays',
    ];

    public function handle(): int
    {
        $results = [];

        $dbStatus = 'PASS';
        $dbMsg = 'Database connected';
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = 'FAIL';
            $dbMsg = 'DB connection failed: '.$e->getMessage();
        }
        $results['database'] = ['status' => $dbStatus, 'message' => $dbMsg];

        $missing = [];
        foreach ($this->requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                $missing[] = $table;
            }
        }
        $results['schema'] = [
            'status' => $missing ? 'FAIL' : 'PASS',
            'message' => $missing ? 'Missing tables: '.implode(', ', $missing) : 'All required tables present ('.count($this->requiredTables).')',
            'missing' => $missing,
        ];

        $tz = config('app.timezone');
        $results['timezone'] = ['status' => ($tz === 'Africa/Algiers' ? 'PASS' : 'WARN'), 'message' => 'Timezone: '.$tz];

        $jsonFiles = ['lang/ar.json', 'lang/en.json', 'lang/fr.json'];
        $jsonErrors = [];
        foreach ($jsonFiles as $file) {
            if (! file_exists(base_path($file))) {
                $jsonErrors[] = $file.' missing';

                continue;
            }
            $content = file_get_contents(base_path($file));
            $decoded = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $jsonErrors[] = $file.' invalid JSON';
            }
        }
        $results['localization'] = ['status' => ($jsonErrors ? 'FAIL' : 'PASS'), 'message' => $jsonErrors ? implode('; ', $jsonErrors) : 'All locale files valid'];

        $pwaErrors = [];
        try {
            if (file_exists(base_path('public/manifest.webmanifest'))) {
                $manifest = json_decode(file_get_contents(base_path('public/manifest.webmanifest')), true);
                $manifestDir = $manifest['dir'] ?? null;
                if ($manifestDir !== 'rtl') {
                    $pwaErrors[] = 'manifest dir is '.$manifestDir;
                }
            } else {
                $pwaErrors[] = 'manifest missing';
            }
            if (! file_exists(base_path('public/sw.js'))) {
                $pwaErrors[] = 'sw.js missing';
            }
        } catch (\Exception $e) {
            $pwaErrors[] = $e->getMessage();
        }
        $results['pwa'] = ['status' => ($pwaErrors ? 'FAIL' : 'PASS'), 'message' => $pwaErrors ? implode('; ', $pwaErrors) : 'PWA assets OK (dir=rtl)'];

        $provider = config('services.ai_planner.provider');
        $apiKey = config('services.ai_planner.api_key');
        $results['ai'] = ['status' => filled($provider) ? 'PASS' : 'WARN', 'message' => 'Provider: '.($provider ?: 'none').' | Key: '.(filled($apiKey) ? 'configured' : 'not configured')];

        if ($this->option('json')) {
            $this->output->writeln(json_encode(['status' => 'ok', 'checks' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->line('=== SAJEL HEALTH CHECK ===');
            foreach ($results as $key => $data) {
                $this->line("[{$data['status']}] {$key}: {$data['message']}");
            }
        }

        $hasFail = collect($results)->contains(fn ($r) => $r['status'] === 'FAIL');

        return $hasFail ? self::FAILURE : self::SUCCESS;
    }
}

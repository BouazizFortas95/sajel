<?php

namespace App\Console\Commands;

use App\Services\SchoolRestoreService;
use Illuminate\Console\Command;

class SajelRestoreCommand extends Command
{
    protected $signature = 'sajel:restore
                            {file : Path to the backup JSON file}
                            {--dry-run : Simulate restoration without saving changes to the database}
                            {--wipe : Wipe existing database records before restoring}
                            {--force : Force the operation without interactive confirmation}';

    protected $description = 'Restore Sajel institutional data and academic records from a JSON backup file';

    public function handle(SchoolRestoreService $service): int
    {
        $file = $this->argument('file');
        $dryRun = (bool) $this->option('dry-run');
        $wipe = (bool) $this->option('wipe');
        $force = (bool) $this->option('force');

        if (! file_exists($file) || ! is_readable($file)) {
            $this->error("Backup file not found or unreadable: {$file}");

            return 1;
        }

        $this->info('=== SAJEL INSTITUTIONAL RESTORE ===');
        $this->line("Reading backup from: <comment>{$file}</comment>");

        $inspection = $service->inspect(file_get_contents($file));

        if (! $inspection['valid']) {
            $this->error('Backup inspection failed:');
            foreach ($inspection['errors'] as $error) {
                $this->line("  <fg=red>✗</> {$error}");
            }

            return 1;
        }

        $this->table(
            ['Property', 'Backup Value'],
            [
                ['School Name', $inspection['school_name'] ?? 'N/A'],
                ['Academic Year', $inspection['academic_year'] ?? 'N/A'],
                ['Generated At', $inspection['generated_at'] ?? 'N/A'],
                ['Has Full Table Data', $inspection['has_tables'] ? 'Yes' : 'No (Config only)'],
                ['Total Records', (string) $inspection['total_rows']],
                ['Execution Mode', $dryRun ? 'DRY-RUN (Simulated)' : 'LIVE RESTORE'],
                ['Wipe Existing Data', $wipe ? 'YES (Clean Restore)' : 'NO (Merge/Append)'],
            ]
        );

        if (! empty($inspection['warnings'])) {
            $this->warn('Warnings detected:');
            foreach ($inspection['warnings'] as $warning) {
                $this->line("  <fg=yellow>⚠</> {$warning}");
            }
        }

        if (! empty($inspection['table_counts'])) {
            $tableRows = [];
            foreach ($inspection['table_counts'] as $table => $count) {
                $tableRows[] = [$table, $count];
            }
            $this->table(['Table', 'Records in Backup'], $tableRows);
        }

        if ($dryRun) {
            $this->info('Running simulation (dry-run)...');
            $result = $service->restoreFromFile($file, dryRun: true, wipeExisting: $wipe);

            $this->info($result['message'] ?? 'Dry-run finished.');
            $this->line("Simulated in <comment>{$result['duration_ms']} ms</comment>.");

            return 0;
        }

        if (! $force) {
            if ($wipe) {
                $confirmed = $this->confirm(
                    'CAUTION: --wipe flag is active. This will delete existing school data before restoring. Do you wish to continue?',
                    false
                );
            } else {
                $confirmed = $this->confirm(
                    'Are you sure you want to restore this backup into the active database?',
                    true
                );
            }

            if (! $confirmed) {
                $this->warn('Operation cancelled by user.');

                return 0;
            }
        }

        $this->info('Executing restoration...');
        $result = $service->restoreFromFile($file, dryRun: false, wipeExisting: $wipe);

        if (! ($result['success'] ?? false)) {
            $this->error('Restoration failed:');
            foreach ($result['errors'] ?? ['Unknown error occurred.'] as $err) {
                $this->line("  <fg=red>✗</> {$err}");
            }

            return 1;
        }

        $this->info('✓ '.$result['message']);
        $this->line("Total records restored: <comment>{$result['total_restored']}</comment> across ".count($result['restored_counts'])." tables in <comment>{$result['duration_ms']} ms</comment>.");

        return 0;
    }
}

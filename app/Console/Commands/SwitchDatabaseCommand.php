<?php

namespace App\Console\Commands;

use App\Services\DatabaseSwitchService;
use Illuminate\Console\Command;

class SwitchDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sajel:switch-db
                            {mode? : Target mode ("clean" or "seeded")}
                            {--status : Show database status without switching}
                            {--reset-clean : Re-initialize clean database to pristine state}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Switch between the Seeded (Demo) database and the Clean (Developer only) database';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseSwitchService $service): int
    {
        if ($this->option('reset-clean')) {
            $this->info('Re-initializing clean database (database_clean.sqlite)...');
            $service->resetCleanDb();
            $this->info('[PASS] Clean database re-initialized with all tables, developer user, and platform settings.');

            return self::SUCCESS;
        }

        if ($this->option('status')) {
            $status = $service->getStatus();
            $this->line('=== SAJEL DATABASE STATUS ===');
            $this->line('Active Mode:    '.strtoupper($status['mode']).' — '.$status['mode_label']);
            $this->line('Tables Count:   '.$status['tables_count']);
            $this->line('Users Count:    '.$status['users_count']);
            $this->line('Students Count: '.$status['students_count']);
            $this->line('Active File:    '.$status['files']['active']['path'].' ('.$status['files']['active']['size_mb'].' MB)');
            $this->line('Clean File:     '.$status['files']['clean']['path'].' ('.$status['files']['clean']['size_mb'].' MB)');
            $this->line('Seeded File:    '.$status['files']['seeded']['path'].' ('.$status['files']['seeded']['size_mb'].' MB)');

            return self::SUCCESS;
        }

        $targetMode = $this->argument('mode');
        if ($targetMode && ! in_array(strtolower($targetMode), [DatabaseSwitchService::MODE_CLEAN, DatabaseSwitchService::MODE_SEEDED], true)) {
            $this->error('Invalid target mode. Valid modes are "clean" or "seeded".');

            return self::FAILURE;
        }

        $target = $targetMode ? strtolower($targetMode) : null;
        $result = $service->switch($target);

        $this->newLine();
        $this->info('========================================================');
        $this->info('  [SAJEL DATABASE SWITCHER]  ');
        $this->info('========================================================');
        $this->line(' Switched from: '.strtoupper($result['from']));
        $this->line(' Switched to:   '.strtoupper($result['to']));
        $this->line(' Message:       '.$result['message']);
        $this->newLine();

        $status = $service->getStatus();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Active Database Mode', strtoupper($status['mode'])],
                ['Mode Description', $status['mode_label']],
                ['Total Tables', $status['tables_count']],
                ['Registered Users', $status['users_count']],
                ['Enrolled Students', $status['students_count']],
                ['Active Database Size', $status['files']['active']['size_mb'].' MB'],
            ]
        );

        return self::SUCCESS;
    }
}

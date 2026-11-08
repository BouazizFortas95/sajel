<?php

namespace App\Console\Commands;

use App\Services\DatabaseSwitchService;
use Illuminate\Console\Command;

class DbSwitchAliasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:switch
                            {mode? : Target mode ("clean" or "seeded")}
                            {--status : Show database status without switching}
                            {--reset-clean : Re-initialize clean database to pristine state}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alias for sajel:switch-db (Switch between Seeded and Clean databases)';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseSwitchService $service): int
    {
        return $this->call(SwitchDatabaseCommand::class, [
            'mode' => $this->argument('mode'),
            '--status' => $this->option('status'),
            '--reset-clean' => $this->option('reset-clean'),
        ]);
    }
}

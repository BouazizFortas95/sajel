<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DailyLogPedagogicalPlanSyncService;
use Illuminate\Console\Command;

class SyncPedagogicalPlansFromDailyLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedagogical:sync-plans
                            {--teacher= : Specific teacher ID to sync}
                            {--year= : Academic year (e.g. 2026-2027)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically scan completed daily logs and sync pedagogical plans and monthly milestones';

    /**
     * Execute the console command.
     */
    public function handle(DailyLogPedagogicalPlanSyncService $service): int
    {
        $teacherId = $this->option('teacher');
        $academicYear = $this->option('year');
        $teacher = null;

        if ($teacherId) {
            $teacher = User::find($teacherId);
            if (! $teacher) {
                $this->error("Teacher with ID [{$teacherId}] not found.");

                return self::FAILURE;
            }
        }

        $this->info('Starting pedagogical plan synchronization from daily logs...');

        $results = $service->bulkSync($teacher, $academicYear);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Teachers Processed', $results['teachers_count']],
                ['Monthly Plans Certified', $results['synced_months']],
                ['Annual Plans Certified', $results['synced_annual']],
            ]
        );

        $this->info('Pedagogical plans synchronized successfully.');

        return self::SUCCESS;
    }
}

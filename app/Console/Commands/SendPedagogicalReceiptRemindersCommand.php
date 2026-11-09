<?php

namespace App\Console\Commands;

use App\Services\PedagogicalPlanReceiptService;
use App\Services\PedagogicalReceiptReminderService;
use Illuminate\Console\Command;

class SendPedagogicalReceiptRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedagogical:send-reminders
                            {--type= : Specific reminder item (annual_plan, monthly_plan, diagnostic_test, quiz_1, quiz_2, quiz_3, exam_1, exam_2, exam_3)}
                            {--dry-run : Display pending teachers without sending notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automatic administrative reminders to teachers for pending pedagogical plans and evaluation deliverables';

    /**
     * Execute the console command.
     */
    public function handle(PedagogicalReceiptReminderService $service): int
    {
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        if (! $type) {
            // Default to current month plan or annual plan based on academic calendar
            $currentMonth = app(PedagogicalPlanReceiptService::class)->getCurrentAcademicMonthKey() ?? 'september';
            $type = $currentMonth;
        }

        $pendingTeachers = $service->getPendingTeachers($type);

        $this->info("Found {$pendingTeachers->count()} teachers pending for [{$type}].");

        if ($pendingTeachers->isEmpty()) {
            $this->info('All teachers have submitted this item. No reminders needed.');

            return self::SUCCESS;
        }

        foreach ($pendingTeachers as $teacher) {
            $this->line("- [{$teacher->id}] {$teacher->name} ({$teacher->primarySubject?->name})");
        }

        if ($dryRun) {
            $this->warn('Dry-run mode active. No notifications were sent.');

            return self::SUCCESS;
        }

        $sentCount = $service->sendBulkReminders($type);
        $this->info("Successfully sent {$sentCount} reminder notifications.");

        return self::SUCCESS;
    }
}


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
                            {--auto : Automatically detect and remind for all active deliverables due in the current academic calendar}
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
        $auto = $this->option('auto');
        $dryRun = $this->option('dry-run');

        $typesToCheck = [];

        if ($type) {
            $typesToCheck = [$type];
        } elseif ($auto) {
            $typesToCheck = $this->resolveDueItems();
        } else {
            $currentMonth = app(PedagogicalPlanReceiptService::class)->getCurrentAcademicMonthKey() ?? 'september';
            $typesToCheck = [$currentMonth];
        }

        $totalSent = 0;

        foreach ($typesToCheck as $itemKey) {
            $pendingTeachers = $service->getPendingTeachers($itemKey);

            if ($pendingTeachers->isEmpty()) {
                $this->line("All teachers have submitted [{$itemKey}]. No reminders needed.");

                continue;
            }

            $this->info("Found {$pendingTeachers->count()} teachers pending for [{$itemKey}]:");

            foreach ($pendingTeachers as $teacher) {
                $this->line("  - [{$teacher->id}] {$teacher->name} ({$teacher->primarySubject?->name})");
            }

            if ($dryRun) {
                $this->warn("Dry-run mode active. No notifications were sent for [{$itemKey}].");

                continue;
            }

            $sentCount = $service->sendBulkReminders($itemKey);
            $totalSent += $sentCount;
            $this->info("Sent {$sentCount} reminder notifications for [{$itemKey}].");
        }

        if (! $dryRun) {
            $this->info("Synchronization complete. Total reminders sent: {$totalSent}.");
        }

        return self::SUCCESS;
    }

    /**
     * Intelligently resolve which deliverables are currently due based on the calendar month.
     *
     * @return array<int, string>
     */
    public function resolveDueItems(): array
    {
        $month = (int) now()->format('n');
        $items = [];

        // Annual plan due in Sep, Oct, Nov
        if (in_array($month, [9, 10, 11], true)) {
            $items[] = 'annual_plan';
        }

        // Current academic month plan
        $currentMonthKey = app(PedagogicalPlanReceiptService::class)->getCurrentAcademicMonthKey();
        if ($currentMonthKey) {
            $items[] = $currentMonthKey;
        }

        // Evaluation deliverables by term
        if (in_array($month, [9, 10], true)) {
            $items[] = 'diagnostic_test';
        }
        if (in_array($month, [10, 11], true)) {
            $items[] = 'homework_1';
            $items[] = 'quiz_1';
        }
        if (in_array($month, [11, 12], true)) {
            $items[] = 'exam_1';
        }
        if (in_array($month, [1, 2], true)) {
            $items[] = 'homework_2';
            $items[] = 'quiz_2';
        }
        if (in_array($month, [2, 3], true)) {
            $items[] = 'exam_2';
        }
        if (in_array($month, [4, 5], true)) {
            $items[] = 'homework_3';
            $items[] = 'quiz_3';
            $items[] = 'exam_3';
        }

        return array_values(array_unique($items));
    }
}

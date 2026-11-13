<?php

namespace App\Console\Commands;

use App\Models\Subject;
use App\Models\User;
use App\Services\CurriculumPlanGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class SeedCurriculumCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sajel:seed-curriculum
                            {--subject=all : Subject name, code, ID, or "all"}
                            {--level=all : Middle school level (1, 2, 3, 4, or "all")}
                            {--teacher= : Optional teacher ID, email, name, or "all" to link schedules for}
                            {--force : Force overwrite existing curriculum domains}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed authentic Algerian middle school curriculum and generate plans for teachers and subjects';

    /**
     * Execute the console command.
     */
    public function handle(CurriculumPlanGeneratorService $generator): int
    {
        $this->info('===========================================================');
        $this->info('  توليد المناهج والتوزيعات الرسمية للتعليم المتوسط — سجل   ');
        $this->info('===========================================================');

        $subjectOpt = $this->option('subject');
        $levelOpt = $this->option('level');
        $teacherOpt = $this->option('teacher');
        $force = (bool) $this->option('force');

        // Resolve levels
        $levels = ($levelOpt === 'all' || blank($levelOpt))
            ? [1, 2, 3, 4]
            : [max(1, min(4, (int) $levelOpt))];

        // 1. If teacher is specified
        if (filled($teacherOpt)) {
            $teachers = ($teacherOpt === 'all')
                ? User::where('role', 'teacher')->get()
                : User::where('role', 'teacher')
                    ->where(function ($q) use ($teacherOpt) {
                        $q->where('id', $teacherOpt)
                            ->orWhere('email', $teacherOpt)
                            ->orWhere('name', 'like', "%{$teacherOpt}%");
                    })->get();

            if ($teachers->isEmpty()) {
                $this->warn("لم يتم العثور على أي أستاذ يطابق: {$teacherOpt}");

                return self::FAILURE;
            }

            $this->info("جاري تهيئة المناهج والتوزيعات لـ {$teachers->count()} أستاذ...");
            $rows = [];

            foreach ($teachers as $t) {
                $subject = $subjectOpt !== 'all' ? $this->resolveSubject($subjectOpt) : null;
                $res = $generator->generateForTeacher($t, $subject, $levels);

                $levelSummaries = [];
                foreach ($res['levels'] as $lvlNum => $detail) {
                    $levelSummaries[] = "السنة {$lvlNum}: ".($detail['status'] === 'created' ? "تم التوليد ({$detail['resources_count']} مورد)" : 'موجود مسبقاً');
                }

                $rows[] = [
                    $t->name,
                    $res['subject'],
                    implode(' | ', $levelSummaries),
                ];
            }

            $this->table(['الأستاذ', 'المادة', 'المستويات والتوزيعات'], $rows);
            $this->info('تم إسناد الجداول وتوليد المناهج للأساتذة بنجاح.');

            return self::SUCCESS;
        }

        // 2. Resolve subjects
        $subjects = $this->resolveSubjects($subjectOpt);

        if ($subjects->isEmpty()) {
            $this->warn("لم يتم العثور على أي مادة مطابقة لـ: {$subjectOpt}");

            return self::FAILURE;
        }

        $this->info("جاري معالجة {$subjects->count()} مادة على المستويات [".implode(', ', $levels).']...');
        $rows = [];

        foreach ($subjects as $subj) {
            foreach ($levels as $lvlNum) {
                $levelModel = $generator->findLevelByNumber($lvlNum);
                if (! $levelModel) {
                    continue;
                }

                $result = $generator->generateForSubjectAndLevel($subj, $levelModel, $force);
                $statusText = $result['status'] === 'created'
                    ? "تم التوليد ({$result['domains_count']} ميادين، {$result['sequences_count']} مقاطع، {$result['resources_count']} موارد)"
                    : "موجود مسبقاً ({$result['domains_count']} ميادين)";

                $rows[] = [
                    $subj->name,
                    $levelModel->name,
                    $statusText,
                ];
            }
        }

        $this->table(['المادة', 'المستوى', 'النتيجة'], $rows);
        $this->info('اكتمل توليد المناهج والتوزيعات بنجاح!');

        return self::SUCCESS;
    }

    private function resolveSubject(string $input): ?Subject
    {
        if (is_numeric($input)) {
            return Subject::find($input);
        }

        return Subject::where('name', 'like', "%{$input}%")
            ->orWhere('code', $input)
            ->first();
    }

    /**
     * @return Collection<int, Subject>
     */
    private function resolveSubjects(string $input)
    {
        if ($input === 'all' || blank($input)) {
            return Subject::orderBy('id')->get();
        }

        $subj = $this->resolveSubject($input);

        return $subj ? Subject::where('id', $subj->id)->get() : Subject::query()->whereRaw('1 = 0')->get();
    }
}

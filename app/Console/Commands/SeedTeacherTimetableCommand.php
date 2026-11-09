<?php

namespace App\Console\Commands;

use App\Enums\DayOfWeek;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Console\Command;

class SeedTeacherTimetableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sajel:seed-teacher-timetable
                            {--teacher=فرطاس بوعزيز : Teacher name or part of name}
                            {--subject=الإعلام الآلي : Subject name}
                            {--from=2026-09-20 : Effective start date (valid_from)}
                            {--until= : Deprecation or end date (valid_until)}
                            {--clear : Clear existing schedules for this teacher before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed weekly timetable sessions for the teacher (e.g. Bouaziz Fertass - Informatics)';

    /**
     * Weekly timetable schedule definition according to Algerian middle-school layout:
     * Slots:
     *   Morning: 08:00-09:00 (1), 09:00-10:00 (2), 10:00-11:00 (3), 11:00-12:00 (4)
     *   Afternoon: 14:00-15:00 (5 / 1 afternoon), 15:00-16:00 (6 / 2 afternoon)
     */
    public const SESSIONS = [
        // يوم الأحد: 4م4، فراغ، 4م1، 1م1، في المساء، 3م4، 1م4
        [
            'day' => DayOfWeek::Sunday,
            'slot_name' => 'الحصة 1 (صباحاً)',
            'start' => '08:00',
            'end' => '09:00',
            'classroom' => '4 م 4',
        ],
        [
            'day' => DayOfWeek::Sunday,
            'slot_name' => 'الحصة 3 (صباحاً)',
            'start' => '10:00',
            'end' => '11:00',
            'classroom' => '4 م 1',
        ],
        [
            'day' => DayOfWeek::Sunday,
            'slot_name' => 'الحصة 4 (صباحاً)',
            'start' => '11:00',
            'end' => '12:00',
            'classroom' => '1 م 1',
        ],
        [
            'day' => DayOfWeek::Sunday,
            'slot_name' => 'الحصة 1 (مساءً)',
            'start' => '14:00',
            'end' => '15:00',
            'classroom' => '3 م 4',
        ],
        [
            'day' => DayOfWeek::Sunday,
            'slot_name' => 'الحصة 2 (مساءً)',
            'start' => '15:00',
            'end' => '16:00',
            'classroom' => '1 م 4',
        ],

        // يوم الاثنين: فراغ، 4م3، 1م7، 1م3
        [
            'day' => DayOfWeek::Monday,
            'slot_name' => 'الحصة 2 (صباحاً)',
            'start' => '09:00',
            'end' => '10:00',
            'classroom' => '4 م 3',
        ],
        [
            'day' => DayOfWeek::Monday,
            'slot_name' => 'الحصة 3 (صباحاً)',
            'start' => '10:00',
            'end' => '11:00',
            'classroom' => '1 م 7',
        ],
        [
            'day' => DayOfWeek::Monday,
            'slot_name' => 'الحصة 4 (صباحاً)',
            'start' => '11:00',
            'end' => '12:00',
            'classroom' => '1 م 3',
        ],

        // يوم الثلاثاء: 1م6، 3م2، 1م2
        [
            'day' => DayOfWeek::Tuesday,
            'slot_name' => 'الحصة 1 (صباحاً)',
            'start' => '08:00',
            'end' => '09:00',
            'classroom' => '1 م 6',
        ],
        [
            'day' => DayOfWeek::Tuesday,
            'slot_name' => 'الحصة 2 (صباحاً)',
            'start' => '09:00',
            'end' => '10:00',
            'classroom' => '3 م 2',
        ],
        [
            'day' => DayOfWeek::Tuesday,
            'slot_name' => 'الحصة 3 (صباحاً)',
            'start' => '10:00',
            'end' => '11:00',
            'classroom' => '1 م 2',
        ],

        // يوم الأربعاء: فراغ، 3م4، فراغ، 1م5
        [
            'day' => DayOfWeek::Wednesday,
            'slot_name' => 'الحصة 2 (صباحاً)',
            'start' => '09:00',
            'end' => '10:00',
            'classroom' => '3 م 4',
        ],
        [
            'day' => DayOfWeek::Wednesday,
            'slot_name' => 'الحصة 4 (صباحاً)',
            'start' => '11:00',
            'end' => '12:00',
            'classroom' => '1 م 5',
        ],

        // يوم الخميس: 3م1، 4م2
        [
            'day' => DayOfWeek::Thursday,
            'slot_name' => 'الحصة 1 (صباحاً)',
            'start' => '08:00',
            'end' => '09:00',
            'classroom' => '3 م 1',
        ],
        [
            'day' => DayOfWeek::Thursday,
            'slot_name' => 'الحصة 2 (صباحاً)',
            'start' => '09:00',
            'end' => '10:00',
            'classroom' => '4 م 2',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $teacherName = $this->option('teacher');
        $subjectName = $this->option('subject');

        $teacher = User::query()
            ->where('name', 'like', "%{$teacherName}%")
            ->first();

        if (! $teacher) {
            $teacher = User::query()
                ->where('role', 'teacher')
                ->where('name', 'like', '%بوعزيز%')
                ->first();
        }

        if (! $teacher) {
            $this->error("Teacher '{$teacherName}' not found!");

            return self::FAILURE;
        }

        $subject = Subject::query()
            ->where('name', 'like', "%{$subjectName}%")
            ->first();

        if (! $subject) {
            $subject = Subject::firstOrCreate(
                ['name' => $subjectName],
                ['color' => '#0284c7']
            );
        }

        // Ensure teacher's primary subject is assigned
        if ($teacher->primary_subject_id !== $subject->id) {
            $teacher->primary_subject_id = $subject->id;
            $teacher->save();
        }

        $this->info("Assigning weekly schedule to teacher: [{$teacher->name}] (Subject: [{$subject->name}])...");

        // Cache all classrooms normalized by removing spaces
        $classrooms = Classroom::all()->keyBy(function (Classroom $c) {
            return preg_replace('/\s+/', '', $c->name);
        });

        if ($this->option('clear')) {
            $deleted = Schedule::where('user_id', $teacher->id)->delete();
            $this->warn("Cleared {$deleted} existing schedules for teacher {$teacher->name}.");
        }

        $createdCount = 0;
        $tableData = [];

        $validFrom = $this->option('from') ?: null;
        $validUntil = $this->option('until') ?: null;

        foreach (self::SESSIONS as $index => $sessionDef) {
            $normalizedName = preg_replace('/\s+/', '', $sessionDef['classroom']);
            $classroom = $classrooms->get($normalizedName);

            if (! $classroom) {
                $this->error("Classroom [{$sessionDef['classroom']}] not found!");

                continue;
            }

            $schedule = Schedule::updateOrCreate(
                [
                    'user_id' => $teacher->id,
                    'day_of_week' => $sessionDef['day']->value,
                    'start_time' => $sessionDef['start'],
                    'valid_from' => $validFrom,
                ],
                [
                    'subject_id' => $subject->id,
                    'classroom_id' => $classroom->id,
                    'group_id' => null,
                    'end_time' => $sessionDef['end'],
                    'valid_until' => $validUntil,
                ]
            );

            $createdCount++;
            $tableData[] = [
                $schedule->id,
                $sessionDef['day']->label(),
                $sessionDef['slot_name'],
                "{$sessionDef['start']} - {$sessionDef['end']}",
                $classroom->name,
                $subject->name,
                $schedule->valid_from?->format('Y-m-d') ?? '—',
                $schedule->valid_until?->format('Y-m-d') ?? 'مستمر',
            ];
        }

        $this->table(
            ['ID', 'اليوم', 'الحصة', 'التوقيت', 'القسم', 'المادة', 'تاريخ السريان', 'تاريخ الإقصاء'],
            $tableData
        );

        $this->info("Successfully configured {$createdCount} weekly timetable sessions for [{$teacher->name}].");

        return self::SUCCESS;
    }
}
